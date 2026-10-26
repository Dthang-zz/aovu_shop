<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonHang extends Model
{
    protected $table = 'don_hangs';

    public $timestamps = false;

    // Trạng thái đơn hàng
    public const STATUS_PENDING = 0; // Chờ xác nhận
    public const STATUS_CONFIRMED = 1; // Đã xác nhận
    public const STATUS_SHIPPING = 2; // Đang giao hàng
    public const STATUS_COMPLETED = 3; // Đã hoàn thành
    public const STATUS_CANCELLED = 4; // Đã hủy

    // Trạng thái thanh toán
    public const PAYMENT_UNPAID = 0; // Chưa thanh toán
    public const PAYMENT_PAID = 1; // Đã thanh toán
    public const PAYMENT_REFUNDED = 2; // Đã hoàn tiền

    protected $fillable = [
        'ma_don_hang',
        'tai_khoan_id',
        'ten_nguoi_nhan',
        'email_nguoi_nhan',
        'sdt_nguoi_nhan',
        'dia_chi_nguoi_nhan',
        'ngay_dat',
        'tien_hang',
        'phi_van_chuyen',
        'voucher_id',
        'tien_giam',
        'tong_tien',
        'ghi_chu',
        'phuong_thuc_thanh_toan_id',
        'trang_thai_thanh_toan',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'ngay_dat' => 'datetime',
            'tien_hang' => 'decimal:0',
            'phi_van_chuyen' => 'decimal:0',
            'tien_giam' => 'decimal:0',
            'tong_tien' => 'decimal:0',
            'trang_thai_thanh_toan' => 'integer',
            'trang_thai' => 'integer',
        ];
    }

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'don_hang_id');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ xác nhận',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_SHIPPING => 'Đang giao hàng',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }

    public static function statusBadges(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_SHIPPING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->trang_thai] ?? 'Không xác định';
    }

    public function statusBadge(): string
    {
        return self::statusBadges()[$this->trang_thai] ?? 'secondary';
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->trang_thai_thanh_toan) {
            self::PAYMENT_PAID => 'Đã thanh toán',
            self::PAYMENT_REFUNDED => 'Đã hoàn tiền',
            default => 'Chưa thanh toán',
        };
    }

    public function paymentStatusBadge(): string
    {
        return match ($this->trang_thai_thanh_toan) {
            self::PAYMENT_PAID => 'success',
            self::PAYMENT_REFUNDED => 'secondary',
            default => 'warning',
        };
    }
}
