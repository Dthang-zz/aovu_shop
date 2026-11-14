<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReturnRequest extends Model
{
    public const STATUS_PENDING = 0;

    public const STATUS_PROCESSING = 1;

    public const STATUS_APPROVED = 2;

    public const STATUS_REJECTED = 3;

    public const STATUS_COMPLETED = 4;

    public const STATUS_REFUNDED = 5;

    public const STATUS_AWAITING_RETURN = 6;

    public const STATUS_RETURN_RECEIVED = 7;

    public const STATUS_INSPECTED = 8;

    public const STATUS_EXCHANGE_SHIPPED = 9;

    public const TYPE_EXCHANGE = 1;

    public const TYPE_RETURN = 2;

    public const RETURN_WINDOW_DAYS = 7;

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
            self::STATUS_AWAITING_RETURN => 'Chờ nhận hàng hoàn',
            self::STATUS_RETURN_RECEIVED => 'Đã nhận hàng hoàn',
            self::STATUS_INSPECTED => 'Đã kiểm tra hàng',
            self::STATUS_EXCHANGE_SHIPPED => 'Đã gửi hàng đổi',
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
            self::STATUS_AWAITING_RETURN => 'warning',
            self::STATUS_RETURN_RECEIVED => 'info',
            self::STATUS_INSPECTED => 'primary',
            self::STATUS_EXCHANGE_SHIPPED => 'info',
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

    public function workflow(): HasOne
    {
        return $this->hasOne(ReturnRequestWorkflow::class, 'return_request_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReturnRequestHistory::class, 'return_request_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * @return array<int, int>
     */
    public static function allowedStatuses(int $currentStatus, int $requestType, bool $collectionWaived = false): array
    {
        return match ($currentStatus) {
            self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_REJECTED],
            self::STATUS_PROCESSING => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => $collectionWaived
                ? [self::STATUS_AWAITING_RETURN, self::STATUS_INSPECTED]
                : [self::STATUS_AWAITING_RETURN],
            self::STATUS_AWAITING_RETURN => [self::STATUS_RETURN_RECEIVED],
            self::STATUS_RETURN_RECEIVED => [self::STATUS_INSPECTED, self::STATUS_REJECTED],
            self::STATUS_INSPECTED => $requestType === self::TYPE_RETURN
                ? []
                : [self::STATUS_EXCHANGE_SHIPPED],
            self::STATUS_REFUNDED, self::STATUS_EXCHANGE_SHIPPED => [self::STATUS_COMPLETED],
            default => [],
        };
    }

    /**
     * Resolve when an order was completed, falling back to the status history and order date for legacy orders.
     */
    public static function orderCompletedAt(object $order): ?Carbon
    {
        if (filled($order->ngay_hoan_thanh ?? null)) {
            return Carbon::parse($order->ngay_hoan_thanh);
        }

        if (Schema::hasTable('lich_su_trang_thai_don_hangs')) {
            $completedAt = DB::table('lich_su_trang_thai_don_hangs')
                ->where('don_hang_id', $order->id)
                ->where('trang_thai', Order::STATUS_COMPLETED)
                ->max('ngay_cap_nhat');

            if ($completedAt !== null) {
                return Carbon::parse($completedAt);
            }
        }

        return filled($order->ngay_dat ?? null) ? Carbon::parse($order->ngay_dat) : null;
    }

    public static function isWithinReturnWindow(object $order): bool
    {
        $completedAt = self::orderCompletedAt($order);

        return $completedAt === null
            || $completedAt->copy()->addDays(self::RETURN_WINDOW_DAYS)->endOfDay()->gte(now());
    }

    /**
     * Amount the customer actually paid for the returned units: the sale price minus their share of the order voucher.
     */
    public static function refundableAmount(int $unitPrice, int $quantity, int $orderVoucherDiscount, int $orderSubtotal): int
    {
        $itemsValue = max(0, $unitPrice * $quantity);
        $voucherShare = $orderSubtotal > 0
            ? (int) round($orderVoucherDiscount * $itemsValue / $orderSubtotal)
            : 0;

        return max(0, $itemsValue - min($itemsValue, $voucherShare));
    }
}
