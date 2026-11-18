<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Voucher extends Model
{
    public const TYPE_PERCENT = 1;
    public const TYPE_FIXED = 2;

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    protected $table = 'vouchers';

    public $timestamps = false;

    protected $fillable = [
        'ma_voucher',
        'mo_ta',
        'loai_giam',
        'gia_tri_giam',
        'giam_toi_da',
        'don_toi_thieu',
        'so_luong',
        'da_su_dung',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'loai_giam' => 'integer',
            'gia_tri_giam' => 'integer',
            'giam_toi_da' => 'integer',
            'don_toi_thieu' => 'integer',
            'so_luong' => 'integer',
            'da_su_dung' => 'integer',
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
            'trang_thai' => 'boolean',
        ];
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($keyword) {
            $subQuery
                ->where('ma_voucher', 'like', "%{$keyword}%")
                ->orWhere('mo_ta', 'like', "%{$keyword}%");
        });
    }

    public function scopeFilterStatus(
        Builder $query,
        ?string $status
    ): Builder {
        return match ($status) {
            'active' => $query->where('trang_thai', self::STATUS_ACTIVE),
            'inactive' => $query->where('trang_thai', self::STATUS_INACTIVE),
            default => $query,
        };
    }

    public function scopeFilterType(Builder $query, $type): Builder
    {
        if ($type === null || $type === '') {
            return $query;
        }

        return $query->where('loai_giam', (int) $type);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('trang_thai', self::STATUS_ACTIVE)
            ->where(function (Builder $subQuery) use ($now) {
                $subQuery->whereNull('ngay_bat_dau')
                    ->orWhere('ngay_bat_dau', '<=', $now);
            })
            ->where(function (Builder $subQuery) use ($now) {
                $subQuery->whereNull('ngay_ket_thuc')
                    ->orWhere('ngay_ket_thuc', '>=', $now);
            })
            ->where(function (Builder $subQuery) {
                $subQuery->whereNull('so_luong')
                    ->orWhereColumn('da_su_dung', '<', 'so_luong');
            });
    }

    public static function stats(): array
    {
        $query = static::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)
                ->where('trang_thai', self::STATUS_ACTIVE)
                ->count(),
            'total_used' => (clone $query)->sum('da_su_dung'),
        ];
    }

    public static function discountTypes(): array
    {
        return [
            self::TYPE_PERCENT => 'Giảm theo phần trăm (%)',
            self::TYPE_FIXED => 'Giảm số tiền cố định (VNĐ)',
        ];
    }

    public function discountTypeLabel(): string
    {
        return self::discountTypes()[$this->loai_giam]
            ?? 'Không xác định';
    }

    public function formattedDiscount(): string
    {
        if ($this->loai_giam === self::TYPE_PERCENT) {
            return number_format(
                (float) $this->gia_tri_giam,
                0,
                ',',
                '.'
            ) . '%';
        }

        return number_format(
            (float) $this->gia_tri_giam,
            0,
            ',',
            '.'
        ) . ' VNĐ';
    }

    public function isExpired(): bool
    {
        return $this->ngay_ket_thuc !== null
            && $this->ngay_ket_thuc->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->ngay_bat_dau !== null
            && $this->ngay_bat_dau->isFuture();
    }

    public function isRunning(): bool
    {
        if (
            ! $this->trang_thai
            || $this->isExpired()
            || $this->isUpcoming()
        ) {
            return false;
        }

        return $this->remainingQuantity() === null
            || $this->remainingQuantity() > 0;
    }

    public function remainingQuantity(): ?int
    {
        if ($this->so_luong === null) {
            return null;
        }

        return max(
            0,
            $this->so_luong - (int) $this->da_su_dung
        );
    }

    public function usagePercent(): int
    {
        if (! $this->so_luong || $this->so_luong <= 0) {
            return 0;
        }

        return min(
            100,
            (int) round(
                ((int) $this->da_su_dung / $this->so_luong) * 100
            )
        );
    }

    public function statusLabel(): string
    {
        if (! $this->trang_thai) {
            return 'Đã tắt';
        }

        if ($this->isUpcoming()) {
            return 'Sắp diễn ra';
        }

        if ($this->isExpired()) {
            return 'Hết hạn';
        }

        if ($this->remainingQuantity() === 0) {
            return 'Hết lượt';
        }

        return 'Đang hoạt động';
    }

    public function statusBadge(): string
    {
        if (
            ! $this->trang_thai
            || $this->isExpired()
            || $this->remainingQuantity() === 0
        ) {
            return 'secondary';
        }

        if ($this->isUpcoming()) {
            return 'info';
        }

        return 'success';
    }

    public function hasOrders(): bool
    {
        if (
            ! Schema::hasTable('don_hangs')
            || ! Schema::hasColumn('don_hangs', 'voucher_id')
        ) {
            return false;
        }

        return DB::table('don_hangs')
            ->where('voucher_id', $this->getKey())
            ->exists();
    }
}
