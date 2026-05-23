<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\FailedState;
use Workflow\Attribute\Flag;
use Workflow\Attribute\Label;

#[FailedState]
#[Label('Rejected')]
#[Color('#e53935')]
#[Flag('done')]
class RejectedState extends BaseReleaseState
{
}
