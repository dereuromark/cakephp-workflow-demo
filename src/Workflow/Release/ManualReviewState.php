<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Label;
use Workflow\Attribute\RequireReason;
use Workflow\Attribute\Transition;

#[Label('Manual Review')]
#[Color('#ffb300')]
#[RequireReason(['reject'])]
#[Transition(to: TestingState::class, name: 'retry_check')]
#[Transition(to: StagingState::class, name: 'approve', happy: true)]
#[Transition(to: RejectedState::class, name: 'reject')]
class ManualReviewState extends BaseReleaseState
{
    #[Command('retry_check')]
    public function resetAndRetry(): void
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return;
        }
        $entity->set('check_attempts', 0);
        $entity->set('fixed', true); // simulate the dev pushing a fix
        $entity->set('notes', 'Reviewer pushed a fix and re-triggered the automated checks.');
    }

    #[Command('approve')]
    public function approveManually(): void
    {
        $this->getEntity()?->set('notes', 'Reviewer approved the release manually, overriding the checks.');
    }
}
