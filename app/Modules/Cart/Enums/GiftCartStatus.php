<?php

declare(strict_types=1);

namespace Modules\Cart\Enums;

use Modules\Core\Enums\BaseEnum;

/**
 * Gift Cart status enumeration
 */
class GiftCartStatus extends BaseEnum
{
    public const PENDING = 'pending';
    public const PARTIAL = 'partial';
    public const COMPLETED = 'completed';
    public const EXPIRED = 'expired';
    public const CANCELLED = 'cancelled';
}
