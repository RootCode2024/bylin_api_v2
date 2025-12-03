<?php

declare(strict_types=1);

namespace Modules\Catalogue\Enums;

use Modules\Core\Enums\BaseEnum;

/**
 * Product status enumeration
 */
class ProductStatus extends BaseEnum
{
    public const DRAFT = 'draft';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const OUT_OF_STOCK = 'out_of_stock';
    public const DISCONTINUED = 'discontinued';
}
