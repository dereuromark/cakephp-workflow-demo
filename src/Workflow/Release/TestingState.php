<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Label;
use Workflow\Attribute\Timeout;
use Workflow\Attribute\Transition;

#[Label('Testing')]
#[Color('#42a5f5')]
#[Timeout('PT2S', 'run_check')]
#[Transition(to: EvaluatingState::class, name: 'run_check', happy: true)]
class TestingState extends BaseReleaseState
{
    #[Command('run_check')]
    public function runAutomatedChecks(): void
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return;
        }
        $attempt = (int)$entity->get('check_attempts') + 1;
        $entity->set('check_attempts', $attempt);
        $entity->set('notes', sprintf('Automated test + smoke-check run #%d executed.', $attempt));
    }
}
