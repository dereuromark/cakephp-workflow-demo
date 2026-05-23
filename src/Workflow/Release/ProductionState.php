<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;
use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Label;
use Workflow\Attribute\Timeout;
use Workflow\Attribute\Transition;

#[Label('Production Rollout')]
#[Color('#26a69a')]
#[Timeout('PT2S', 'go_live')]
#[Transition(to: ReleasedState::class, name: 'go_live', happy: true)]
class ProductionState extends BaseReleaseState
{
    use LocatorAwareTrait;

    /**
     * Simulate a transient rollout crash: the first go_live THROWS (logged as an
     * 'error' transition with the exception details); the retry then succeeds.
     * Attempt count is read from the audit log.
     */
    #[Command('go_live')]
    public function rollout(): void
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return;
        }
        $priorAttempts = $this->fetchTable('Workflow.WorkflowTransitions')
            ->find()
            ->where([
                'workflow_name' => 'release',
                'entity_table' => 'Releases',
                'entity_id' => (string)$entity->get('id'),
                'transition_name' => 'go_live',
            ])
            ->count();

        if ($priorAttempts < 1) {
            throw new RuntimeException('Production rollout failed: deploy script exited with code 1 (transient).');
        }

        usleep(300000); // fake rollout work
        $entity->set('notes', 'Rolled out to 100% of production traffic.');
    }
}
