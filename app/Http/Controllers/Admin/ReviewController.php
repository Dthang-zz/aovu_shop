<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:san_phams,id'],
            'stars' => ['nullable', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'product_id.exists' => 'Sản phẩm được chọn không tồn tại.',
            'stars.in' => 'Số sao phải từ 1 đến 5.',
            'to.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
        ]);

        $query = $this->reviewQuery();
        $term = trim((string) ($filters['q'] ?? ''));

        if ($term !== '') {
            $query->where(function ($query) use ($term): void {
                $query->where('reviews.noi_dung', 'like', "%{$term}%")
                    ->orWhere('accounts.ho_ten', 'like', "%{$term}%")
                    ->orWhere('accounts.email', 'like', "%{$term}%")
                    ->orWhere('products.ten_san_pham', 'like', "%{$term}%")
                    ->orWhere('orders.ma_don_hang', 'like', "%{$term}%");
            });
        }

        if (isset($filters['product_id'])) {
            $query->where('reviews.san_pham_id', $filters['product_id']);
        }

        if (isset($filters['stars'])) {
            $query->where('reviews.so_sao', $filters['stars']);
        }

        if (isset($filters['from'])) {
            $query->whereDate('reviews.ngay_dang', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate('reviews.ngay_dang', '<=', $filters['to']);
        }

        $statisticsQuery = Review::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(DISTINCT san_pham_id) as products_reviewed')
            ->selectRaw('AVG(so_sao) as average_rating');

        if (Schema::hasColumn('danh_gias', 'phan_hoi')) {
            $statisticsQuery->selectRaw(
                'SUM(CASE WHEN phan_hoi IS NOT NULL AND phan_hoi != ? THEN 1 ELSE 0 END) as replied',
                [''],
            );
        } else {
            $statisticsQuery->selectRaw('0 as replied');
        }

        $statistics = $statisticsQuery->first();

        $productReviewStats = Review::query()
            ->select('san_pham_id')
            ->selectRaw('COUNT(*) as review_count, AVG(so_sao) as average_rating')
            ->groupBy('san_pham_id');

        $productStatistics = DB::table('san_phams as products')
            ->joinSub($productReviewStats, 'product_reviews', function ($join): void {
                $join->on('product_reviews.san_pham_id', '=', 'products.id');
            })
            ->select([
                'products.id',
                'products.ten_san_pham',
                'product_reviews.review_count',
                'product_reviews.average_rating',
            ])
            ->orderByDesc('product_reviews.review_count')
            ->orderBy('products.ten_san_pham')
            ->limit(10)
            ->get();

        $reviews = $query
            ->orderByDesc('reviews.ngay_dang')
            ->orderByDesc('reviews.id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'products' => DB::table('san_phams')->orderBy('ten_san_pham')->get(['id', 'ten_san_pham']),
            'filters' => $filters,
            'statistics' => $statistics,
            'productStatistics' => $productStatistics,
        ]);
    }

    public function show(Review $review): View
    {
        $review = $this->reviewQuery()
            ->where('reviews.id', $review->getKey())
            ->firstOrFail();
        $review->photos = $this->photoUrls($review->hinh_anh);

        return view('admin.reviews.show', [
            'review' => $review,
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'phan_hoi' => ['nullable', 'string', 'max:5000'],
        ], [
            'phan_hoi.max' => 'Phản hồi không được vượt quá 5000 ký tự.',
        ]);

        $reply = trim((string) ($data['phan_hoi'] ?? ''));

        if ($reply !== '' && ! Schema::hasColumn('danh_gias', 'phan_hoi')) {
            throw ValidationException::withMessages([
                'phan_hoi' => 'Cơ sở dữ liệu chưa có cột phản hồi. Vui lòng chạy migration trước khi lưu phản hồi.',
            ]);
        }

        DB::transaction(function () use ($review, $reply): void {
            $record = Review::query()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $record->update([
                'phan_hoi' => $reply !== '' ? $reply : null,
            ]);
        });

        return redirect()
            ->route('admin.reviews.show', $review)
            ->with('success', 'Đã cập nhật phản hồi đánh giá.');
    }

    private function reviewQuery(): Builder
    {
        return DB::table('danh_gias as reviews')
            ->leftJoin('san_phams as products', 'products.id', '=', 'reviews.san_pham_id')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'reviews.tai_khoan_id')
            ->leftJoin('chi_tiet_don_hangs as order_items', 'order_items.id', '=', 'reviews.chi_tiet_don_hang_id')
            ->leftJoin('don_hangs as orders', 'orders.id', '=', 'order_items.don_hang_id')
            ->select([
                'reviews.*',
                'products.ten_san_pham as product_name',
                'accounts.ho_ten as customer_name',
                'accounts.email as customer_email',
                'order_items.ten_san_pham as purchased_product_name',
                'order_items.don_gia as purchased_item_price',
                'order_items.so_luong as purchased_item_quantity',
                'orders.ma_don_hang as order_number',
                'orders.ngay_dat as order_date',
                'orders.tong_tien as order_total',
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function photoUrls(?string $photos): array
    {
        if ($photos === null || trim($photos) === '') {
            return [];
        }

        $decodedPhotos = json_decode($photos, true);
        $paths = is_array($decodedPhotos) ? $decodedPhotos : [$photos];

        return collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->map(fn (string $path): string => Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : Storage::disk('public')->url($path))
            ->values()
            ->all();
    }
}
