<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'Pending';
    case Preparing = 'Preparing';
    case Ready = 'Ready';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Preparing, self::Ready], true);
    }
}
