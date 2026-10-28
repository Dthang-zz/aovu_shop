<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_SHIPPING = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELLED = 4;

    public const PAYMENT_UNPAID = 0;
    public const PAYMENT_PAID = 1;

    protected $table = 'don_hangs';

    public $timestamps = false;

    protected $fillable = [
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'tai_khoan_id' => 'integer',
            'ngay_dat' => 'datetime',
            'tien_hang' => 'float',
            'phi_van_chuyen' => 'float',
            'tien_giam' => 'float',
            'tong_tien' => 'float',
            'trang_thai_thanh_toan' => 'integer',
            'trang_thai' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ xác nhận',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_SHIPPING => 'Đang giao',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statusBadges(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'primary',
            self::STATUS_SHIPPING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'secondary',
        ];
    }

    /**
     * @return array<int, int>
     */
    public static function allowedNextStatuses(int $status): array
    {
        return match ($status) {
            self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_SHIPPING, self::STATUS_CANCELLED],
            self::STATUS_SHIPPING => [self::STATUS_COMPLETED],
            default => [],
        };
    }

    public static function paymentStatusLabel(?int $status): string
    {
        return match ($status) {
            self::PAYMENT_UNPAID => 'Chưa thanh toán',
            self::PAYMENT_PAID => 'Đã thanh toán',
            default => 'Không xác định',
        };
    }
}
