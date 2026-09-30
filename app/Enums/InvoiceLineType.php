<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceLineType: string
{
    case Base = 'base';
    case Overage = 'overage';
}
