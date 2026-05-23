<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $version
 * @property string $state
 * @property int $check_attempts
 * @property bool $fixed
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $state_changed_at
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Release extends Entity
{
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
