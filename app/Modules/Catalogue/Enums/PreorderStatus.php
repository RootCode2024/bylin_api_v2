<?php

declare(strict_types=1);

namespace Modules\Catalogue\Enums;

use Modules\Core\Enums\BaseEnum;

/**
 * Preorder status enumeration
 */
class PreorderStatus extends BaseEnum
{
    public const PENDING = 'pending';
    public const AVAILABLE = 'available';
    public const NOTIFIED = 'notified';
    public const SHIPPED = 'shipped';
}
