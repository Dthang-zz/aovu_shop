<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::query()
            ->with('parent')
            ->withCount(['children', 'products']);

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($query) use ($term) {
                $query->where('ten_danh_muc', 'like', "%{$term}%")
                    ->orWhere('mo_ta', 'like', "%{$term}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('trang_thai', true);
        } elseif ($request->query('status') === 'hidden') {
            $query->where('trang_thai', false);
        }

        if ($request->filled('parent_id')) {
            $query->where('danh_muc_cha_id', $request->query('parent_id'));
        }

        $orderedCategories = $this->orderHierarchically(
            $query->orderBy('ten_danh_muc')->get()
        );
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $categories = new LengthAwarePaginator(
            $orderedCategories->forPage($currentPage, $perPage)->values(),
            $orderedCategories->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('admin.categories.index', [
            'categories' => $categories,
            'parents' => Category::query()->orderBy('ten_danh_muc')->get(),
            'totalCount' => Category::count(),
            'activeCount' => Category::where('trang_thai', true)->count(),
            'hiddenCount' => Category::where('trang_thai', false)->count(),
        ]);
    }

    public function show(Category $category): View
    {
        $category->load('parent')
            ->loadCount('products')
            ->load(['children' => fn ($query) => $query->withCount('products')]);

        return view('admin.categories.show', [
            'category' => $category,
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(['trang_thai' => true]),
            'parents' => Category::query()->orderBy('ten_danh_muc')->get(),
            'formTitle' => 'Thêm danh mục',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validatedData($request));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã thêm danh mục mới.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => $this->availableParents($category),
            'formTitle' => 'Chỉnh sửa danh mục',
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validatedData($request, $category));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Không thể xóa danh mục đang được sản phẩm sử dụng.');
        }

        DB::transaction(function () use ($category): void {
            $category->children()->update(['danh_muc_cha_id' => null]);
            $category->delete();
        });

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã xóa danh mục.');
    }

    private function orderHierarchically(Collection $categories): Collection
    {
        $categoriesById = $categories->keyBy(
            fn (Category $category): int => (int) $category->getKey()
        );
        $childrenByParentId = $categories->groupBy('danh_muc_cha_id');
        $orderedCategories = new Collection;
        $visited = [];

        $appendCategory = function (Category $category, int $depth) use (
            &$appendCategory,
            &$visited,
            $childrenByParentId,
            $orderedCategories
        ): void {
            $categoryId = (int) $category->getKey();
            if (isset($visited[$categoryId])) {
                return;
            }

            $visited[$categoryId] = true;
            $category->setAttribute('hierarchy_depth', $depth);
            $orderedCategories->push($category);

            $childrenByParentId->get($categoryId, new Collection)
                ->sortBy('ten_danh_muc')
                ->each(fn (Category $child) => $appendCategory($child, $depth + 1));
        };

        $roots = $categories->filter(
            fn (Category $category): bool => $category->danh_muc_cha_id === null
                || ! $categoriesById->has($category->danh_muc_cha_id)
        );

        $roots->sortBy('ten_danh_muc')
            ->each(fn (Category $category) => $appendCategory($category, 0));
        $categories->each(fn (Category $category) => $appendCategory($category, 0));

        return $orderedCategories;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Category $category = null): array
    {
        $parentRules = ['nullable', 'integer', Rule::exists('danh_mucs', 'id')];
        if ($category) {
            $parentRules[] = Rule::notIn([$category->getKey()]);
        }

        $validator = validator($request->all(), [
            'ten_danh_muc' => ['required', 'string', 'max:255'],
            'danh_muc_cha_id' => $parentRules,
            'mo_ta' => ['nullable', 'string', 'max:5000'],
            'trang_thai' => ['required', 'boolean'],
        ], [
            'ten_danh_muc.required' => 'Vui lòng nhập tên danh mục.',
            'ten_danh_muc.max' => 'Tên danh mục không được vượt quá 255 ký tự.',
            'danh_muc_cha_id.exists' => 'Danh mục cha không tồn tại.',
            'danh_muc_cha_id.not_in' => 'Danh mục không thể là danh mục cha của chính nó.',
            'mo_ta.max' => 'Mô tả không được vượt quá 5000 ký tự.',
            'trang_thai.required' => 'Vui lòng chọn trạng thái.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ]);

        $validator->after(function ($validator) use ($request, $category) {
            if (! $category || ! $request->filled('danh_muc_cha_id')) {
                return;
            }

            if ($this->parentChainContains(
                (int) $request->input('danh_muc_cha_id'),
                (int) $category->getKey()
            )) {
                $validator->errors()->add(
                    'danh_muc_cha_id',
                    'Không thể chọn danh mục con làm danh mục cha.'
                );
            }
        });

        return $validator->validate();
    }

    private function parentChainContains(int $parentId, int $categoryId): bool
    {
        $visited = [];

        while ($parentId !== 0 && ! isset($visited[$parentId])) {
            if ($parentId === $categoryId) {
                return true;
            }

            $visited[$parentId] = true;
            $parentId = (int) Category::query()
                ->whereKey($parentId)
                ->value('danh_muc_cha_id');
        }

        return false;
    }

    private function availableParents(Category $category): Collection
    {
        $excludedIds = [$category->getKey()];
        $pendingIds = [$category->getKey()];

        while ($pendingIds !== []) {
            $children = Category::query()
                ->whereIn('danh_muc_cha_id', $pendingIds)
                ->pluck('id')
                ->all();
            $pendingIds = array_values(array_diff($children, $excludedIds));
            $excludedIds = array_merge($excludedIds, $pendingIds);
        }

        return Category::query()
            ->whereNotIn('id', $excludedIds)
            ->orderBy('ten_danh_muc')
            ->get();
    }
}
