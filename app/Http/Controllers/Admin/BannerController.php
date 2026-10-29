<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Banner::query();

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($query) use ($term) {
                $query->where('ten_banner', 'like', "%{$term}%")
                    ->orWhere('lien_ket', 'like', "%{$term}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('trang_thai', true);
        } elseif ($request->query('status') === 'hidden') {
            $query->where('trang_thai', false);
        }

        return view('admin.banners.index', [
            'banners' => $query->orderBy('thu_tu')->orderByDesc('id')->paginate(10)->withQueryString(),
            'totalCount' => Banner::count(),
            'activeCount' => Banner::where('trang_thai', true)->count(),
            'hiddenCount' => Banner::where('trang_thai', false)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.form', [
            'banner' => new Banner(['trang_thai' => true, 'thu_tu' => 0]),
            'formTitle' => 'Thêm banner',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $imagePath = $request->file('hinh_anh')->store('banners', 'public');

        if ($imagePath === false) {
            throw new RuntimeException('Không thể lưu ảnh banner vào ổ đĩa public.');
        }

        $data['hinh_anh'] = $imagePath;

        try {
            Banner::create($data);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePath);
            throw $exception;
        }

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Đã thêm banner mới.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', [
            'banner' => $banner,
            'formTitle' => 'Chỉnh sửa banner',
        ]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validatedData($request, $banner);
        $newImagePath = null;
        $oldImagePath = $banner->hinh_anh;

        if ($request->hasFile('hinh_anh')) {
            $newImagePath = $request->file('hinh_anh')->store('banners', 'public');

            if ($newImagePath === false) {
                throw new RuntimeException('Không thể lưu ảnh banner vào ổ đĩa public.');
            }

            $data['hinh_anh'] = $newImagePath;
        }

        try {
            $banner->update($data);
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath !== null && $oldImagePath !== null) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Đã cập nhật banner.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $imagePath = $banner->hinh_anh;
        $banner->delete();

        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Đã xóa banner.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Banner $banner = null): array
    {
        $data = $request->validate([
            'ten_banner' => ['required', 'string', 'max:255'],
            'hinh_anh' => [
                Rule::requiredIf($banner === null),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'lien_ket' => ['nullable', 'string', 'max:2048'],
            'thu_tu' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'trang_thai' => ['required', 'boolean'],
        ], [
            'ten_banner.required' => 'Vui lòng nhập tên banner.',
            'hinh_anh.required' => 'Vui lòng chọn ảnh banner.',
            'hinh_anh.image' => 'Tệp tải lên phải là ảnh.',
            'hinh_anh.mimes' => 'Ảnh banner phải có định dạng JPG, PNG hoặc WebP.',
            'hinh_anh.max' => 'Ảnh banner không được vượt quá 5 MB.',
            'lien_ket.max' => 'Liên kết không được vượt quá 2048 ký tự.',
            'thu_tu.required' => 'Vui lòng nhập thứ tự hiển thị.',
            'thu_tu.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'thu_tu.min' => 'Thứ tự hiển thị không được nhỏ hơn 0.',
            'thu_tu.max' => 'Thứ tự hiển thị quá lớn.',
            'trang_thai.required' => 'Vui lòng chọn trạng thái.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ]);

        unset($data['hinh_anh']);
        $data['lien_ket'] = $data['lien_ket'] ?? null;

        return $data;
    }
}
