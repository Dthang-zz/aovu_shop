<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReturnRequest extends Model
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 2;

    public const STATUS_REJECTED = 3;

    public const STATUS_COMPLETED = 4;

    public const STATUS_RETURN_RECEIVED = 7;

    /**
     * Statuses of requests that are still being handled (they reserve quantity and block deleting the customer).
     *
     * @var array<int, int>
     */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_RETURN_RECEIVED,
    ];

    public const TYPE_EXCHANGE = 1;

    public const TYPE_RETURN = 2;

    public const RETURN_WINDOW_DAYS = 7;

    public const ACTION_APPROVE = 'approve';

    public const ACTION_REJECT = 'reject';

    public const ACTION_RECEIVE = 'receive';

    public const ACTION_COMPLETE = 'complete';

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
        'so_tien_hoan',
        'ma_giao_dich_hoan',
        'bien_the_thay_the_id',
        'ngay_nhan_hang',
        'ngay_hoan_tat',
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
            'so_tien_hoan' => 'integer',
            'bien_the_thay_the_id' => 'integer',
            'ngay_nhan_hang' => 'datetime',
            'ngay_hoan_tat' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_RETURN_RECEIVED => 'Đã nhận hàng',
            self::STATUS_COMPLETED => 'Hoàn tất',
            self::STATUS_REJECTED => 'Từ chối',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statusBadges(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_RETURN_RECEIVED => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED => 'danger',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_EXCHANGE => 'Đổi hàng',
            self::TYPE_RETURN => 'Trả hàng',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->trang_thai] ?? 'Không xác định';
    }

    public function typeLabel(): string
    {
        return self::types()[$this->loai_yeu_cau] ?? 'Yêu cầu khác';
    }

    /**
     * Actions staff can take on a request in the given status.
     *
     * @return array<int, string>
     */
    public static function allowedActions(int $status): array
    {
        return match ($status) {
            self::STATUS_PENDING => [self::ACTION_APPROVE, self::ACTION_REJECT],
            self::STATUS_APPROVED => [self::ACTION_RECEIVE, self::ACTION_REJECT],
            self::STATUS_RETURN_RECEIVED => [self::ACTION_COMPLETE, self::ACTION_REJECT],
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

    /**
     * Last moment a return/exchange can still be requested for the order.
     */
    public static function returnDeadline(object $order): ?Carbon
    {
        return self::orderCompletedAt($order)?->copy()->addDays(self::RETURN_WINDOW_DAYS)->endOfDay();
    }

    public static function isWithinReturnWindow(object $order): bool
    {
        $deadline = self::returnDeadline($order);

        return $deadline === null || $deadline->gte(now());
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
