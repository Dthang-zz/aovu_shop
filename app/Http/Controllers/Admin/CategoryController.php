<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::query()->with('parent')->withCount('children');

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

        $categories = $query
            ->orderByRaw('CASE WHEN danh_muc_cha_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('ten_danh_muc')
            ->paginate(10)
            ->withQueryString();

        $productCounts = collect();
        if (Schema::hasTable('san_phams')) {
            $productCounts = DB::table('san_phams')
                ->select('danh_muc_id')
                ->selectRaw('COUNT(*) as total')
                ->whereNotNull('danh_muc_id')
                ->groupBy('danh_muc_id')
                ->pluck('total', 'danh_muc_id');
        }

        return view('admin.categories.index', [
            'categories' => $categories,
            'parents' => Category::query()->orderBy('ten_danh_muc')->get(),
            'productCounts' => $productCounts,
            'totalCount' => Category::count(),
            'activeCount' => Category::where('trang_thai', true)->count(),
            'hiddenCount' => Category::where('trang_thai', false)->count(),
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
        if ($category->children()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Không thể xóa danh mục đang có danh mục con.');
        }

        if (Schema::hasTable('san_phams')
            && DB::table('san_phams')->where('danh_muc_id', $category->getKey())->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Không thể xóa danh mục đang được sản phẩm sử dụng.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã xóa danh mục.');
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
            if (!$category || !$request->filled('danh_muc_cha_id')) {
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

        while ($parentId !== 0 && !isset($visited[$parentId])) {
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
