<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\Color;
use Workflow\Attribute\InitialState;
use Workflow\Attribute\Label;
use Workflow\Attribute\Transition;

#[InitialState]
#[Label('Draft')]
#[Color('#9e9e9e')]
#[Transition(to: BuildingState::class, name: 'submit', happy: true)]
class DraftState extends BaseReleaseState
{
}
