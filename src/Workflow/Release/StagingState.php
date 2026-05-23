<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Cake\ORM\Locator\LocatorAwareTrait;
use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Guard;
use Workflow\Attribute\Label;
use Workflow\Attribute\Timeout;
use Workflow\Attribute\Transition;

#[Label('Staging')]
#[Color('#26a69a')]
#[Timeout('PT2S', 'deploy_staging')]
#[Transition(to: CanaryState::class, name: 'deploy_staging', happy: true)]
class StagingState extends BaseReleaseState
{
    use LocatorAwareTrait;

    /**
     * Simulate a flaky deploy: the first attempt is blocked, so the release stays in
     * 'staging'; the retry then succeeds. Attempt count is derived from the audit log
     * (no dedicated column).
     */
    #[Guard('deploy_staging')]
    public function deployIsStable(): bool|string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return true;
        }
        $priorAttempts = $this->fetchTable('Workflow.WorkflowTransitions')
            ->find()
            ->where([
                'workflow_name' => 'release',
                'entity_table' => 'Releases',
                'entity_id' => (string)$entity->get('id'),
                'transition_name' => 'deploy_staging',
            ])
            ->count();

        if ($priorAttempts < 1) {
            return 'Staging deploy failed (flaky infrastructure) — staying in staging, will retry.';
        }

        return true;
    }

    #[Command('deploy_staging')]
    public function deploy(): void
    {
        usleep(300000); // fake deploy work
        $this->getEntity()?->set('notes', 'Deployed the build to staging.');
    }
}
