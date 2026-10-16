<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;
    public const STATUS_COMPLETED = 4;
    public const STATUS_REFUNDED = 5;

    public const TYPE_EXCHANGE = 1;
    public const TYPE_RETURN = 2;

    protected $table = 'yeu_cau_doi_tras';

    public $timestamps = false;

    protected $fillable = [
        'don_hang_id',
        'chi_tiet_don_hang_id',
        'tai_khoan_id',
        'loai_yeu_cau',
        'so_luong',
        'ly_do',
        'hinh_anh',
        'phan_hoi',
        'ngay_tao',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'don_hang_id' => 'integer',
            'chi_tiet_don_hang_id' => 'integer',
            'tai_khoan_id' => 'integer',
            'loai_yeu_cau' => 'integer',
            'so_luong' => 'integer',
            'ngay_tao' => 'datetime',
            'trang_thai' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ tiếp nhận',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_APPROVED => 'Đã chấp nhận',
            self::STATUS_REJECTED => 'Đã từ chối',
            self::STATUS_COMPLETED => 'Đã hoàn tất',
            self::STATUS_REFUNDED => 'Đã ghi nhận hoàn tiền',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statusBadges(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REFUNDED => 'success',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->trang_thai] ?? 'Không xác định';
    }

    public function typeLabel(): string
    {
        return match ($this->loai_yeu_cau) {
            self::TYPE_EXCHANGE => 'Đổi hàng',
            self::TYPE_RETURN => 'Trả hàng',
            default => 'Yêu cầu khác',
        };
    }
}
