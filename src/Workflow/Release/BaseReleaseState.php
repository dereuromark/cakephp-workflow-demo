<?php
declare(strict_types=1);
namespace App\Workflow\Release;

use Workflow\Attribute\StateMachine;
use Workflow\State\AbstractState;

#[StateMachine(name: 'release', table: 'Releases', field: 'state')]
abstract class BaseReleaseState extends AbstractState
{
}
