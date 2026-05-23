<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Demo: a software-release pipeline driven entirely by dereuromark/cakephp-workflow.
 *
 * - manual transitions  -> buttons the user clicks (submit, retry_check, approve, reject)
 * - timeout transitions -> auto-advance after a delay (the "fake sleep"), fired by run()
 * - automatic transitions with conditions -> the 3x check loop + branch in EvaluatingState
 *
 * @property \App\Model\Table\ReleasesTable $Releases
 */
class ReleasesController extends AppController
{
    public function index(): void
    {
        $releases = $this->Releases->find()->orderBy(['id' => 'DESC'])->all();
        $this->set(compact('releases'));
    }

    public function add(): ?Response
    {
        $this->request->allowMethod(['post']);
        $version = trim((string)$this->request->getData('version'));
        if ($version === '') {
            $version = sprintf('v%d.%d.0', random_int(1, 4), random_int(0, 9));
        }
        $release = $this->Releases->newEntity(['version' => $version, 'state' => 'draft']);
        $this->Releases->saveOrFail($release);
        $this->Flash->success('Created release ' . h($release->version) . '.');

        return $this->redirect(['action' => 'view', $release->id]);
    }

    public function view(int $id): void
    {
        $release = $this->Releases->get($id);
        $behavior = $this->Releases->getBehavior('Workflow');
        $definition = $behavior->getWorkflowDefinition();
        $stateObj = $definition->getState($release->state);

        // Split the available transitions into manual (buttons) vs auto (timeout/automatic).
        $available = $behavior->getAvailableTransitions($release);
        $timeoutTransitions = array_map(
            static fn ($t) => $t->getTransition(),
            $stateObj->getTimeouts(),
        );
        $manual = [];
        foreach ($available as $name) {
            $transition = $definition->getTransition($name);
            if ($transition->isAutomatic() || in_array($name, $timeoutTransitions, true)) {
                continue;
            }
            $manual[] = $name;
        }

        $isFinal = $stateObj->isFinal() || $stateObj->isFailed();
        $isAuto = !$isFinal && ($timeoutTransitions !== [] || $manual === []);

        $history = $this->fetchTable('Workflow.WorkflowTransitions')
            ->find('forEntity', workflow: 'release', table: 'Releases', id: (string)$id)
            ->all()
            ->toArray();

        $this->set(compact('release', 'definition', 'stateObj', 'manual', 'isFinal', 'isAuto', 'history'));
    }

    public function transition(int $id): ?Response
    {
        $this->request->allowMethod(['post']);
        $release = $this->Releases->get($id);
        $name = (string)$this->request->getData('transition');

        $context = ['triggered_by' => 'user'];
        $reason = $this->request->getData('reason');
        if ($reason) {
            $context['reason'] = (string)$reason;
        }

        $result = $this->Releases->getBehavior('Workflow')->transition($release, $name, $context);
        if ($result->isSuccess()) {
            $this->Flash->success(sprintf('Applied "%s".', $name));
        } elseif ($result->isBlocked()) {
            $this->Flash->warning(sprintf('"%s" was blocked: %s', $name, implode(', ', $result->getBlockedBy())));
        } else {
            $this->Flash->error(sprintf('"%s" failed.', $name));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Poll endpoint: fire any timeouts that are now due for this release and report state.
     */
    public function run(int $id): Response
    {
        $this->request->allowMethod(['get', 'post']);
        $fired = $this->processDueTimeouts($id);

        $release = $this->Releases->get($id);
        $behavior = $this->Releases->getBehavior('Workflow');
        $stateObj = $behavior->getWorkflowDefinition()->getState($release->state);
        $isFinal = $stateObj->isFinal() || $stateObj->isFailed();
        $hasTimeout = $stateObj->getTimeouts() !== [];

        return $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'state' => $release->state,
                'fired' => $fired,
                'attempts' => (int)$release->check_attempts,
                'isFinal' => $isFinal,
                'keepPolling' => !$isFinal && $hasTimeout,
            ]));
    }

    private function processDueTimeouts(int $id): int
    {
        $timeoutsTable = $this->fetchTable('Workflow.WorkflowTimeouts');
        /** @var array<\Workflow\Model\Entity\WorkflowTimeout> $due */
        $due = $timeoutsTable->find()
            ->where([
                'entity_table' => 'Releases',
                'entity_id' => $id,
                'processed' => false,
                'due_at <=' => DateTime::now(),
            ])
            ->orderBy(['due_at' => 'ASC'])
            ->all()
            ->toArray();

        $fired = 0;
        $behavior = $this->Releases->getBehavior('Workflow');
        foreach ($due as $timeout) {
            $release = $this->Releases->get($id);
            // Skip stale timeouts whose state no longer matches.
            if ($release->state !== $timeout->current_state) {
                $timeout->processed = true;
                $timeoutsTable->saveOrFail($timeout);

                continue;
            }
            $result = $behavior->transition($release, $timeout->transition_name, ['triggered_by' => 'timeout']);
            if ($result->isError()) {
                // A command threw (e.g. the transient rollout crash): the release stays
                // in its current state. Log the exception to the app log and retry.
                \Cake\Log\Log::error(sprintf(
                    'Release #%d "%s" transition errored: %s',
                    $id,
                    $timeout->transition_name,
                    $result->getError()?->getMessage() ?? 'unknown',
                ));
                $timeout->due_at = DateTime::now()->addSeconds(2);
                $timeoutsTable->saveOrFail($timeout);
                $fired++;

                continue;
            }
            if ($result->isBlocked()) {
                // A guard blocked the timed transition (e.g. the flaky staging deploy):
                // the release correctly stays in its current state — retry after the delay.
                $timeout->due_at = DateTime::now()->addSeconds(2);
                $timeoutsTable->saveOrFail($timeout);
                $fired++; // surface the blocked attempt in the UI

                continue;
            }
            $timeout->processed = true;
            $timeoutsTable->saveOrFail($timeout);
            if ($result->isSuccess()) {
                $fired++;
            }
        }

        return $fired;
    }
}
