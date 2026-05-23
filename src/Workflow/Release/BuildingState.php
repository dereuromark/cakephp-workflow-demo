<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\Command;
use Workflow\Attribute\Label;
use Workflow\Attribute\Timeout;
use Workflow\Attribute\Transition;

#[Label('Building')]
#[Color('#42a5f5')]
#[Timeout('PT2S', 'built')]
#[Transition(to: TestingState::class, name: 'built', happy: true)]
class BuildingState extends BaseReleaseState
{
    #[Command('built')]
    public function compile(): void
    {
        usleep(300000); // fake build work
        $this->getEntity()?->set('notes', 'Compiled artifacts and packaged the build.');
    }
}
