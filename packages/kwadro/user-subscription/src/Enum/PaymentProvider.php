<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Enum;

enum PaymentProvider: string
{
    case Privat = 'privat';
    case Monobank = 'monobank';

    public function label(): string
    {
        return match ($this) {
            self::Privat => 'PrivatBank (LiqPay)',
            self::Monobank => 'Monobank',
        };
    }
}
