<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\FinalState;
use Workflow\Attribute\Flag;
use Workflow\Attribute\Label;

#[FinalState]
#[Label('Released')]
#[Color('#43a047')]
#[Flag('done')]
class ReleasedState extends BaseReleaseState
{
}
