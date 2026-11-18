<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::query()->ordered()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.form', [
            'banner' => new Banner(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, true);
        $data['image_path'] = $this->storeImage($request->file('image'));

        try {
            Banner::query()->create($data);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($data['image_path']);

            throw $exception;
        }

        return redirect()->route('admin.banners.index')->with('success', 'Đã tạo banner.');
    }

    public function show(Banner $banner): View
    {
        return view('admin.banners.show', ['banner' => $banner]);
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', ['banner' => $banner]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validatedData($request);
        $previousImage = $banner->image_path;
        $newImage = $request->file('image');

        if ($newImage !== null) {
            $data['image_path'] = $this->storeImage($newImage);
        }

        try {
            $banner->update($data);
        } catch (Throwable $exception) {
            if (isset($data['image_path']) && $data['image_path'] !== $previousImage) {
                Storage::disk('public')->delete($data['image_path']);
            }

            throw $exception;
        }

        if ($newImage !== null) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('admin.banners.index')->with('success', 'Đã cập nhật banner.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $imagePath = $banner->image_path;
        $banner->delete();
        Storage::disk('public')->delete($imagePath);

        return redirect()->route('admin.banners.index')->with('success', 'Đã xóa banner.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $imageRequired = false): array
    {
        $imageRules = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        if ($imageRequired) {
            $imageRules[0] = 'required';
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => $imageRules,
            'link_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề banner.',
            'image.required' => 'Vui lòng chọn ảnh banner.',
            'image.image' => 'Tệp tải lên phải là ảnh.',
            'image.mimes' => 'Ảnh banner chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'image.max' => 'Ảnh banner không được lớn hơn 5120 kilobyte.',
            'link_url.url' => 'Liên kết phải là URL hợp lệ.',
            'sort_order.required' => 'Vui lòng nhập thứ tự hiển thị.',
            'sort_order.min' => 'Thứ tự hiển thị không được nhỏ hơn 0.',
        ]);
    }

    private function storeImage(UploadedFile $image): string
    {
        $path = $image->store('banners', 'public');

        if ($path === false) {
            throw new RuntimeException('Không thể lưu ảnh banner.');
        }

        return $path;
    }
}
