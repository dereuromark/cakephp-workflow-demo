<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Condition;
use Workflow\Attribute\Label;
use Workflow\Attribute\Transition;

#[Label('Evaluating')]
#[Color('#7e57c2')]
// Order matters: first matching condition wins; the last (unconditioned) is the fallback.
#[Transition(to: StagingState::class, name: 'tests_passed', happy: true, automatic: true)]
#[Transition(to: TestingState::class, name: 'retry', automatic: true)]
#[Transition(to: ManualReviewState::class, name: 'escalate', automatic: true)]
class EvaluatingState extends BaseReleaseState
{
    #[Condition('tests_passed')]
    public function checksPassed(): bool
    {
        // Checks only pass once a human has intervened ("fixed" the build) or approved.
        return (bool)$this->getEntity()?->get('fixed');
    }

    #[Condition('retry')]
    public function attemptsRemaining(): bool
    {
        return (int)$this->getEntity()?->get('check_attempts') < 3;
    }

    #[Command('escalate')]
    public function recordEscalation(): void
    {
        $this->getEntity()?->set('notes', 'Automated tests failed 3× — escalated for manual review.');
    }
}
