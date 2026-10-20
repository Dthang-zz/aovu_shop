<?php

namespace App;

enum OrderStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Shipping = 2;
    case Completed = 3;
    case Cancelled = 4;

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            self::Pending->value => 'Chờ xác nhận',
            self::Processing->value => 'Đang xử lý',
            self::Shipping->value => 'Đang giao',
            self::Completed->value => 'Hoàn thành',
            self::Cancelled->value => 'Đã hủy',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function badges(): array
    {
        return [
            self::Pending->value => 'warning',
            self::Processing->value => 'primary',
            self::Shipping->value => 'info',
            self::Completed->value => 'success',
            self::Cancelled->value => 'danger',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value];
    }

    public function badge(): string
    {
        return self::badges()[$this->value];
    }

    /**
     * @return array<int, self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipping, self::Cancelled],
            self::Shipping => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }
}
