<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Label;
use Workflow\Attribute\Timeout;
use Workflow\Attribute\Transition;

#[Label('Canary')]
#[Color('#26a69a')]
#[Timeout('PT2S', 'canary_check')]
#[Transition(to: CanaryEvalState::class, name: 'canary_check', happy: true)]
class CanaryState extends BaseReleaseState
{
    #[Command('canary_check')]
    public function analyzeCanary(): void
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return;
        }
        $attempt = (int)$entity->get('canary_attempts') + 1;
        $entity->set('canary_attempts', $attempt);
        $entity->set('notes', sprintf('Canary analysis pass #%d (error budget + latency).', $attempt));
    }
}
