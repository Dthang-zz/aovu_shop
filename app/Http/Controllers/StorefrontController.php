<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    private const SORT_OPTIONS = [
        'popular',
        'newest',
        'price_asc',
        'price_desc',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:danh_mucs,id'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'color_id' => ['nullable', 'integer'],
            'size_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'string', 'in:'.implode(',', self::SORT_OPTIONS)],
        ]);

        if (isset($filters['min_price'], $filters['max_price'])
            && (float) $filters['max_price'] < (float) $filters['min_price']) {
            throw ValidationException::withMessages([
                'max_price' => 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.',
            ]);
        }

        $query = Product::query()
            ->with('category')
            ->where('trang_thai', true);

        $keyword = trim($filters['q'] ?? '');
        if ($keyword !== '') {
            $query->where(function (Builder $query) use ($keyword) {
                $query->where('ten_san_pham', 'like', "%{$keyword}%")
                    ->orWhere('ma_san_pham', 'like', "%{$keyword}%")
                    ->orWhere('mo_ta', 'like', "%{$keyword}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('danh_muc_id', $filters['category_id']);
        }

        if (isset($filters['min_price'])) {
            $query->whereRaw(
                'COALESCE(NULLIF(gia_khuyen_mai, 0), gia_san_pham) >= ?',
                [(int) $filters['min_price']]
            );
        }

        if (isset($filters['max_price'])) {
            $query->whereRaw(
                'COALESCE(NULLIF(gia_khuyen_mai, 0), gia_san_pham) <= ?',
                [(int) $filters['max_price']]
            );
        }

        $variantFilters = array_filter([
            'mau_sac_id' => $filters['color_id'] ?? null,
            'kich_thuoc_id' => $filters['size_id'] ?? null,
        ], fn (?string $value): bool => $value !== null);
        if ($variantFilters !== []) {
            if (! Schema::hasTable('bien_thes')
                || ! Schema::hasColumn('bien_thes', 'san_pham_id')
                || collect(array_keys($variantFilters))->contains(
                    fn (string $column): bool => ! Schema::hasColumn('bien_thes', $column)
                )) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereExists(function ($variantQuery) use ($variantFilters) {
                    $variantQuery->selectRaw('1')
                        ->from('bien_thes as variants')
                        ->whereColumn('variants.san_pham_id', 'san_phams.id');

                    foreach ($variantFilters as $column => $value) {
                        $variantQuery->where('variants.'.$column, $value);
                    }
                });
            }
        }

        $sort = $filters['sort'] ?? 'popular';
        match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(NULLIF(gia_khuyen_mai, 0), gia_san_pham) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(NULLIF(gia_khuyen_mai, 0), gia_san_pham) desc'),
            'newest' => $query->orderByDesc('ngay_nhap')->orderByDesc('id'),
            default => $query->orderByDesc('da_ban')->orderByDesc('luot_xem')->orderByDesc('id'),
        };

        return view('storefront.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()
                ->where('trang_thai', true)
                ->orderBy('ten_danh_muc')
                ->get(),
            'colors' => $this->availableVariants('mau_sacs', 'mau_sac_id', [
                'mau_sacs.id',
                'mau_sacs.ten_mau',
                'mau_sacs.ma_mau',
            ]),
            'sizes' => $this->availableVariants('kich_thuocs', 'kich_thuoc_id', [
                'kich_thuocs.id',
                'kich_thuocs.ten_kich_thuoc',
            ]),
            'filters' => array_merge($filters, ['q' => $keyword, 'sort' => $sort]),
        ]);
    }

    /**
     * @param  array<int, string>  $columns
     * @return Collection<int, \stdClass>
     */
    private function availableVariants(string $table, string $variantColumn, array $columns): Collection
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasTable('bien_thes')
            || ! Schema::hasColumn('bien_thes', $variantColumn)
            || ! Schema::hasColumn('bien_thes', 'san_pham_id')) {
            return collect();
        }

        return DB::table($table)
            ->join('bien_thes as variants', $table.'.id', '=', 'variants.'.$variantColumn)
            ->join('san_phams as products', 'products.id', '=', 'variants.san_pham_id')
            ->where('products.trang_thai', true)
            ->whereNotNull($table.'.'.($table === 'mau_sacs' ? 'ten_mau' : 'ten_kich_thuoc'))
            ->select($columns)
            ->distinct()
            ->orderBy($table.'.'.($table === 'mau_sacs' ? 'ten_mau' : 'ten_kich_thuoc'))
            ->get();
    }
}
