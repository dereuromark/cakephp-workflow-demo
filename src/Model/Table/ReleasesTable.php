<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ReleasesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('releases');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Workflow.Workflow', [
            'workflow' => 'release',
            'autoLog' => true,        // record every transition for the history view
            'logAllOutcomes' => true, // also log blocked/locked/error
        ]);
    }
}
