<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Condition;
use Workflow\Attribute\Label;
use Workflow\Attribute\Transition;

#[Label('Canary Analysis')]
#[Color('#7e57c2')]
// healthy first (condition), unhealthy is the fallback.
#[Transition(to: ProductionState::class, name: 'canary_healthy', automatic: true)]
#[Transition(to: ManualReviewState::class, name: 'canary_failed', automatic: true)]
class CanaryEvalState extends BaseReleaseState
{
    #[Condition('canary_healthy')]
    public function canaryHealthy(): bool
    {
        // Simulate a regression on the first canary; a re-rollout then passes.
        return (int)$this->getEntity()?->get('canary_attempts') >= 2;
    }

    #[Command('canary_failed')]
    public function recordRegression(): void
    {
        $this->getEntity()?->set('notes', 'Canary is UNHEALTHY (elevated error rate) — rollout halted for review.');
    }
}
