<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

class InvalidCouponException extends BusinessException
{
    protected int $statusCode = 422; // Unprocessable Entity
}
