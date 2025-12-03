<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

class PaymentFailedException extends BusinessException
{
    protected int $statusCode = 402; // Payment Required
}
