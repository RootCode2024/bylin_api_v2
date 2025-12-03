<?php

declare(strict_types=1);

namespace Modules\User\Enums;

use Modules\Core\Enums\BaseEnum;

/**
 * User status enumeration
 */
class UserStatus extends BaseEnum
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const SUSPENDED = 'suspended';
    public const BANNED = 'banned';
}
