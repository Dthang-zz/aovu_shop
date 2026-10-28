<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_LOCKED = 0;

    public const TRANSACTION_REFUND  = 1; // Hoàn tiền vào ví
    public const TRANSACTION_USAGE   = 2; // Sử dụng từ ví
    public const TRANSACTION_ADJUST  = 3; // Điều chỉnh thủ công

    protected $table = 'vi_hoan_tiens';

    public $timestamps = false;

    protected $fillable = [
        'tai_khoan_id',
        'so_du',
        'trang_thai',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected function casts(): array
    {
        return [
            'tai_khoan_id'  => 'integer',
            'so_du'         => 'decimal:0',
            'trang_thai'    => 'integer',
            'ngay_tao'      => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    // ─── Relationships ─────────────────────────────

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'vi_id');
    }

    // ─── Status helpers ────────────────────────────

    public function isActive(): bool
    {
        return $this->trang_thai === self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return match ($this->trang_thai) {
            self::STATUS_ACTIVE => 'Hoạt động',
            self::STATUS_LOCKED => 'Đã khóa',
            default => 'Không xác định',
        };
    }

    public function statusBadge(): string
    {
        return match ($this->trang_thai) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_LOCKED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function transactionTypes(): array
    {
        return [
            self::TRANSACTION_REFUND => 'Hoàn tiền vào ví',
            self::TRANSACTION_USAGE  => 'Sử dụng từ ví',
            self::TRANSACTION_ADJUST => 'Điều chỉnh thủ công',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function transactionBadges(): array
    {
        return [
            self::TRANSACTION_REFUND => 'success',
            self::TRANSACTION_USAGE  => 'danger',
            self::TRANSACTION_ADJUST => 'info',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function transactionIcons(): array
    {
        return [
            self::TRANSACTION_REFUND => 'fas fa-arrow-down',
            self::TRANSACTION_USAGE  => 'fas fa-arrow-up',
            self::TRANSACTION_ADJUST => 'fas fa-sliders-h',
        ];
    }

    // ─── Query scopes ──────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('accounts.ho_ten', 'like', "%{$term}%")
              ->orWhere('accounts.email', 'like', "%{$term}%")
              ->orWhere('accounts.so_dien_thoai', 'like', "%{$term}%");
        });
    }

    public function scopeFilterStatus($query, ?string $status)
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('vi_hoan_tiens.trang_thai', (int) $status);
    }
}
