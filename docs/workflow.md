# The `release` workflow — a walkthrough

The workflow is attribute-defined in [`src/Workflow/Release/`](../src/Workflow/Release), one PHP class per state.
The base class wires the machine to the `releases` table:

```php
#[StateMachine(name: 'release', table: 'Releases', field: 'state')]
abstract class BaseReleaseState extends AbstractState {}
```

## States & transitions

| State | Kind | Leaves via |
|---|---|---|
| `draft` | initial | `submit` (manual) → building |
| `building` | auto | `built` (timeout 2s) → testing |
| `testing` | auto | `run_check` (timeout 2s) → evaluating |
| `evaluating` | branch | `tests_passed` / `retry` / `escalate` (automatic + conditions) |
| `manual_review` | human | `retry_check` / `approve` / `reject` (manual) |
| `staging` | auto | `deploy_staging` (timeout 2s, **guarded**) → canary |
| `canary` | auto | `canary_check` (timeout 2s) → canary analysis |
| `canary_eval` | branch | `canary_healthy` / `canary_failed` (automatic + condition) |
| `production` | auto | `go_live` (timeout 2s, command **may throw**) → released |
| `released` | final | — |
| `rejected` | failed | — |

## The patterns it shows

### 1. Manual vs automatic vs timeout
There is **no `manual` flag** — a transition is manual unless you mark it otherwise:

- **Manual**: a plain `#[Transition(...)]`, triggered by a button (`submit`, `approve`, …).
- **Timeout**: `#[Timeout('PT2S', 'built')]` schedules the transition to fire after a delay — the demo's "fake sleep".
- **Automatic**: `#[Transition(automatic: true)]` is evaluated the moment the entity enters the state.

### 2. A conditional branch with a retry loop (`evaluating`)
Automatic transitions are tried **in declaration order**; the first whose `#[Condition]` passes wins, and an
unconditioned one is the fallback:

```php
#[Transition(to: StagingState::class,      name: 'tests_passed', automatic: true)]
#[Transition(to: TestingState::class,      name: 'retry',        automatic: true)]
#[Transition(to: ManualReviewState::class, name: 'escalate',     automatic: true)] // fallback
class EvaluatingState extends BaseReleaseState
{
    #[Condition('tests_passed')]
    public function checksPassed(): bool { return (bool)$this->getEntity()?->get('fixed'); }

    #[Condition('retry')]
    public function attemptsRemaining(): bool { return (int)$this->getEntity()?->get('check_attempts') < 3; }
}
```

`testing → evaluating → (retry) → testing` loops up to 3×; the loop is *paced* by the testing timeout, so you see
each attempt. After 3 failures the fallback escalates to `manual_review`.

### 3. Guards — a `blocked` outcome (`staging`)
A `#[Guard]` returning a string **blocks** the transition. The release **stays in its current state** — that's the
correct, expected behaviour for a refused transition (it is *not* an error):

```php
#[Guard('deploy_staging')]
public function deployIsStable(): bool|string
{
    if ($this->priorDeployCount() < 1) {
        return 'Staging deploy failed (flaky infrastructure) — staying in staging, will retry.';
    }
    return true;
}
```

### 4. Command exceptions — an `error` outcome (`production`)
A `#[Command]` that **throws** produces an `error` result (the engine wraps it in a `CommandException`). Again the
release stays put; the demo's controller logs it and retries:

```php
#[Command('go_live')]
public function rollout(): void
{
    if ($this->priorGoLiveCount() < 1) {
        throw new \RuntimeException('Production rollout failed: deploy script exited with code 1 (transient).');
    }
    // ... succeed on retry
}
```

## `blocked` vs `error` — and where the details live

These are **distinct** `TransitionResult` outcomes:

- **`blocked`** — a guard / `RequireReason` / not-allowed transition refused it. *Expected control flow.*
- **`error`** — a command threw an exception. *Unexpected runtime failure.*
- (`success`, `locked` are the other two.)

Every attempt is written to the `workflow_transitions` audit table, with details in its JSON `context` column:

| Outcome | `status` | Details in `context` |
|---|---|---|
| blocked | `blocked` | `_blocked_by` → `{ "Guard::method": "reason" }` |
| error | `error` | `_error` → `{ message, class, file, line }` |
| locked | `locked` | `_locked: true` |

You can see these in **three** places in the demo:

1. **The history table** on the release page (the *Detail* column).
2. **The database**: `SELECT status, context FROM workflow_transitions`.
3. **The app log**: `logs/error.log` (the controller calls `Log::error()` on an errored timed transition).

## Two ways to track attempts

The demo intentionally uses both:

- **A column** (`canary_attempts`): a `#[Command]` increments it; a `#[Condition]` reads it.
- **The audit log** (staging): no column — the guard simply counts prior `deploy_staging` rows in
  `workflow_transitions`. Handy when you don't want to add schema just to gate a retry.

## Driving auto-transitions

The plugin advances timed transitions via `bin/cake workflow timeouts` (a cron worker). For an interactive demo,
`ReleasesController::run()` does the same thing on demand for a single release — the view polls it every ~1.2s and
reloads when the state changes, so you watch the pipeline move. Blocked/errored timed transitions are re-armed so
they retry after the delay.
