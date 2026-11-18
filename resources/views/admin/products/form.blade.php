@extends('layouts.admin')

@section('title', $formTitle)
@section('page-heading', $formTitle)
@section('page-subheading', 'Nhập thông tin, giá bán và hình ảnh sản phẩm.')
@section('breadcrumb')
    <a href="{{ route('admin.products.index') }}">Sản phẩm</a>
@endsection

@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if ($product->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="font-weight-bold mb-1">Vui lòng kiểm tra lại thông tin:</div>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Thông tin cơ bản</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="ten_san_pham">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input id="ten_san_pham" type="text" name="ten_san_pham" value="{{ old('ten_san_pham', $product->ten_san_pham) }}" class="form-control @error('ten_san_pham') is-invalid @enderror" maxlength="255" required autofocus placeholder="Ví dụ: Áo sơ mi Linen">
                        @error('ten_san_pham')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ma_san_pham">Mã sản phẩm / SKU <span class="text-danger">*</span></label>
                            <input id="ma_san_pham" type="text" name="ma_san_pham" value="{{ old('ma_san_pham', $product->ma_san_pham) }}" class="form-control @error('ma_san_pham') is-invalid @enderror" maxlength="50" required placeholder="Ví dụ: AO-LINEN-01">
                            @error('ma_san_pham')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="danh_muc_id">Danh mục</label>
                            <select id="danh_muc_id" name="danh_muc_id" class="form-control @error('danh_muc_id') is-invalid @enderror">
                                <option value="">Chưa phân loại</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('danh_muc_id', $product->danh_muc_id) === (string) $category->id)>{{ $category->ten_danh_muc }}{{ $category->trang_thai ? '' : ' (đang ẩn)' }}</option>
                                @endforeach
                            </select>
                            @error('danh_muc_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="gia_san_pham">Giá bán (₫) <span class="text-danger">*</span></label>
                            <input id="gia_san_pham" type="number" name="gia_san_pham" value="{{ old('gia_san_pham', $product->gia_san_pham) }}" class="form-control @error('gia_san_pham') is-invalid @enderror" min="0" max="999999999999" step="1" required placeholder="459000">
                            @error('gia_san_pham')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="gia_khuyen_mai">Giá khuyến mãi (₫)</label>
                            <input id="gia_khuyen_mai" type="number" name="gia_khuyen_mai" value="{{ old('gia_khuyen_mai', $product->gia_khuyen_mai) }}" class="form-control @error('gia_khuyen_mai') is-invalid @enderror" min="0" max="999999999999" step="1" placeholder="Để trống nếu không giảm giá">
                            @error('gia_khuyen_mai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Giá khuyến mãi phải thấp hơn giá bán.</small>
                        </div>
                    </div>
                    <div class="form-group" id="product-stock-group">
                            <label for="so_luong_ton">Số lượng tồn kho sản phẩm</label>
                            <input id="so_luong_ton" type="number" name="so_luong_ton" value="{{ old('so_luong_ton', $product->so_luong_ton ?? 0) }}" class="form-control @error('so_luong_ton') is-invalid @enderror" min="0" max="2147483647" step="1">
                            @error('so_luong_ton')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Dùng khi sản phẩm không có biến thể. Nếu có biến thể, tồn kho sẽ tự tính bằng tổng tồn kho của các biến thể.</small>
                    </div>
                    <div class="form-group">
                        <label for="mo_ta">Mô tả sản phẩm</label>
                        <textarea id="mo_ta" name="mo_ta" rows="7" maxlength="20000" class="form-control @error('mo_ta') is-invalid @enderror" placeholder="Nhập mô tả, chất liệu, hướng dẫn sử dụng...">{{ old('mo_ta', $product->mo_ta) }}</textarea>
                        @error('mo_ta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="chat_lieu">Chất liệu</label>
                        <textarea id="chat_lieu" name="chat_lieu" rows="3" maxlength="10000" class="form-control @error('chat_lieu') is-invalid @enderror" placeholder="Ví dụ: 100% linen tự nhiên, mềm nhẹ và thoáng khí.">{{ old('chat_lieu', $product->chat_lieu) }}</textarea>
                        @error('chat_lieu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="huong_dan_su_dung">Hướng dẫn sử dụng</label>
                        <textarea id="huong_dan_su_dung" name="huong_dan_su_dung" rows="4" maxlength="10000" class="form-control @error('huong_dan_su_dung') is-invalid @enderror" placeholder="Ví dụ: Giặt máy ở chế độ nhẹ, không dùng thuốc tẩy, phơi nơi thoáng mát.">{{ old('huong_dan_su_dung', $product->huong_dan_su_dung) }}</textarea>
                        @error('huong_dan_su_dung')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card mt-3" id="variants-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-layer-group text-primary mr-2"></i>Thuộc tính và biến thể</h3>
                    <span class="badge badge-info">Tùy chọn</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Thêm thuộc tính như Màu sắc, Kích thước. Nhập các giá trị cách nhau bằng dấu phẩy, sau đó tạo tổ hợp biến thể.</p>
                    <div id="attribute-groups">
                        @foreach (old('attribute_groups', $attributeGroups ?? []) as $index => $group)
                            <div class="border rounded p-3 mb-2 attribute-group">
                                <div class="form-row align-items-end">
                                    <div class="form-group col-md-4 mb-2">
                                        <label>Tên thuộc tính</label>
                                        <input type="text" class="form-control attribute-name" name="attribute_groups[{{ $index }}][name]" value="{{ $group['name'] ?? '' }}" maxlength="50" placeholder="Ví dụ: Màu sắc">
                                    </div>
                                    <div class="form-group col-md-7 mb-2">
                                        <label>Giá trị</label>
                                        <input type="text" class="form-control attribute-values" name="attribute_groups[{{ $index }}][values]" value="{{ $group['values'] ?? '' }}" maxlength="500" placeholder="Ví dụ: Đen, Trắng, Xám">
                                    </div>
                                    <div class="form-group col-md-1 mb-2"><button type="button" class="btn btn-outline-danger btn-block remove-attribute" title="Xóa thuộc tính"><i class="fas fa-times"></i></button></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-attribute"><i class="fas fa-plus mr-1"></i>Thêm thuộc tính</button>
                        <button type="button" class="btn btn-sm btn-primary" id="generate-variants"><i class="fas fa-magic mr-1"></i>Tạo biến thể</button>
                    </div>
                    <div id="variant-alert" class="small text-muted mb-2">Chưa có biến thể. Sản phẩm sẽ dùng tồn kho chung.</div>
                    <div id="variant-table-wrap" class="table-responsive" hidden>
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light"><tr><th>Thuộc tính</th><th style="min-width:150px">SKU</th><th style="min-width:130px">Giá riêng (₫)</th><th style="min-width:100px">Tồn kho</th></tr></thead>
                            <tbody id="variant-table-body"></tbody>
                        </table>
                    </div>
                    <small class="form-text text-muted mt-2">Giá riêng có thể để trống để sử dụng giá bán của sản phẩm. Tối đa 100 tổ hợp.</small>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="far fa-image text-primary mr-2"></i>Hình ảnh sản phẩm</h3></div>
                <div class="card-body">
                    @if ($product->images->isNotEmpty())
                        <div class="row">
                            @foreach ($product->images as $image)
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-2 h-100">
                                        <img src="{{ Storage::disk('public')->url($image->link_hinh_anh) }}" alt="Ảnh {{ $product->ten_san_pham }}" class="img-fluid rounded w-100" style="height: 110px; object-fit: cover">
                                        @if ($product->hinh_anh === $image->link_hinh_anh)
                                            <span class="badge badge-primary mt-2">Ảnh đại diện</span>
                                        @endif
                                        <div class="custom-control custom-checkbox mt-2">
                                            <input type="checkbox" id="remove_image_{{ $image->id }}" name="xoa_hinh_anh_ids[]" value="{{ $image->id }}" class="custom-control-input" @checked(in_array((string) $image->id, array_map('strval', old('xoa_hinh_anh_ids', [])), true))>
                                            <label class="custom-control-label small" for="remove_image_{{ $image->id }}">Xóa ảnh này</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4 mb-3 bg-light rounded">
                            <i class="far fa-image mb-2" style="font-size: 2.5rem"></i>
                            <div>Chưa có ảnh sản phẩm.</div>
                        </div>
                    @endif
                    <div class="form-group mb-0">
                        <label for="hinh_anh">{{ $product->images->isNotEmpty() ? 'Thêm ảnh sản phẩm' : 'Chọn ảnh sản phẩm' }}</label>
                        <div class="custom-file">
                            <input type="file" id="hinh_anh" name="hinh_anh[]" class="custom-file-input @error('hinh_anh') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                            <label class="custom-file-label" for="hinh_anh">Chọn một hoặc nhiều ảnh</label>
                        </div>
                        @error('hinh_anh')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('hinh_anh.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">JPG, PNG hoặc WebP; tối đa 5 MB mỗi ảnh. Có thể tải lên tối đa 10 ảnh mỗi lần.</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-sliders-h text-primary mr-2"></i>Trạng thái</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="trang_thai">Trạng thái kinh doanh <span class="text-danger">*</span></label>
                        <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                            <option value="1" @selected((string) old('trang_thai', (int) $product->trang_thai) === '1')>Đang kinh doanh</option>
                            <option value="0" @selected((string) old('trang_thai', (int) $product->trang_thai) === '0')>Đang ẩn</option>
                        </select>
                        @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="noi_bat" name="noi_bat" value="1" class="custom-control-input" @checked((bool) old('noi_bat', $product->noi_bat))>
                        <label class="custom-control-label" for="noi_bat">Đánh dấu sản phẩm nổi bật</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('admin.products.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>{{ $product->exists ? 'Lưu thay đổi' : 'Tạo sản phẩm' }}</button>
    </div>
</form>

@endsection

@section('scripts')
<script>
(() => {
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    const groupContainer = document.getElementById('attribute-groups');
    const tableWrap = document.getElementById('variant-table-wrap');
    const tableBody = document.getElementById('variant-table-body');
    const alertBox = document.getElementById('variant-alert');
    const stockGroup = document.getElementById('product-stock-group');
    const stockInput = document.getElementById('so_luong_ton');
    const skuInput = document.getElementById('ma_san_pham');
    const existingRows = @json(old('variants', $variantRows ?? []));
    let attributeCounter = Array.from(groupContainer.querySelectorAll('.attribute-group')).reduce((nextIndex, row) => {
        const match = row.querySelector('.attribute-name')?.name.match(/^attribute_groups\[(\d+)\]/);
        return match ? Math.max(nextIndex, Number(match[1]) + 1) : nextIndex;
    }, 0);

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const slug = value => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[đĐ]/g, 'd').replace(/[^a-zA-Z0-9]+/g, '-').replace(/^-|-$/g, '').toUpperCase();
    const keyFor = attrs => JSON.stringify(Object.keys(attrs).sort().reduce((result, key) => (result[key] = attrs[key], result), {}));

    const currentGroups = () => Array.from(groupContainer.querySelectorAll('.attribute-group')).map(row => ({
        name: row.querySelector('.attribute-name').value.trim(),
        values: [...new Set(row.querySelector('.attribute-values').value.split(',').map(value => value.trim()).filter(Boolean))]
    })).filter(group => group.name && group.values.length);

    const addGroup = (name = '', values = '') => {
        const groupIndex = attributeCounter++;
        const row = document.createElement('div');
        row.className = 'border rounded p-3 mb-2 attribute-group';
        row.innerHTML = `<div class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2"><label>Tên thuộc tính</label><input type="text" name="attribute_groups[${groupIndex}][name]" class="form-control attribute-name" maxlength="50" placeholder="Ví dụ: Màu sắc" value="${esc(name)}"></div>
            <div class="form-group col-md-7 mb-2"><label>Giá trị</label><input type="text" name="attribute_groups[${groupIndex}][values]" class="form-control attribute-values" maxlength="500" placeholder="Ví dụ: Đen, Trắng, Xám" value="${esc(values)}"></div>
            <div class="form-group col-md-1 mb-2"><button type="button" class="btn btn-outline-danger btn-block remove-attribute" title="Xóa thuộc tính"><i class="fas fa-times"></i></button></div>
        </div>`;
        groupContainer.appendChild(row);
    };

    const setStockMode = hasVariants => {
        stockGroup.hidden = hasVariants;
        stockInput.required = !hasVariants;
    };

    const makeCombinations = groups => {
        let combinations = [{}];
        groups.forEach(group => {
            const next = [];
            combinations.forEach(current => group.values.forEach(value => next.push({...current, [group.name]: value})));
            combinations = next;
        });
        return combinations;
    };

    const renderVariants = (preserveExisting = true) => {
        const groups = currentGroups();
        const previous = new Map();
        tableBody.querySelectorAll('tr').forEach(row => {
            const attrsInput = row.querySelector('input[name$=\"[attributes]\"]');
            if (!attrsInput) return;
            let attrs = {}; try { attrs = JSON.parse(attrsInput.value); } catch (_) {}
            if (Object.keys(attrs).length) previous.set(keyFor(attrs), { sku: row.querySelector('.variant-sku')?.value, price: row.querySelector('.variant-price')?.value, stock: row.querySelector('.variant-stock')?.value, attributes: attrs });
        });
        tableBody.innerHTML = '';
        if (!groups.length) {
            tableWrap.hidden = true;
            alertBox.textContent = 'Chưa có biến thể. Sản phẩm sẽ dùng tồn kho chung.';
            setStockMode(false);
            return;
        }
        const groupNames = groups.map(group => group.name.toLocaleLowerCase());
        if (new Set(groupNames).size !== groupNames.length) {
            tableWrap.hidden = true;
            alertBox.textContent = 'Tên thuộc tính không được trùng nhau.';
            setStockMode(false);
            return;
        }
        const combinations = makeCombinations(groups);
        if (combinations.length > 100) {
            alertBox.textContent = 'Có quá nhiều tổ hợp (' + combinations.length + '). Vui lòng giảm giá trị thuộc tính xuống tối đa 100 biến thể.';
            tableWrap.hidden = true;
            setStockMode(false);
            return;
        }
        if (preserveExisting) {
            existingRows.forEach(row => {
                let attrs = row.attributes;
                if (typeof attrs === 'string') { try { attrs = JSON.parse(attrs); } catch (_) { attrs = {}; } }
                if (attrs && typeof attrs === 'object' && !previous.has(keyFor(attrs))) previous.set(keyFor(attrs), {...row, attributes: attrs});
            });
        }
        combinations.forEach((attrs, index) => {
            const old = previous.get(keyFor(attrs)) || {};
            const generatedSku = [skuInput.value.trim(), ...Object.values(attrs).map(slug)].filter(Boolean).join('-');
            const row = document.createElement('tr');
            const attributesLabel = Object.entries(attrs).map(([name, value]) => `${name}: ${value}`).join(' / ');
            row.innerHTML = `<td><strong>${esc(attributesLabel)}</strong></td>
                <td><input class="form-control form-control-sm variant-sku" maxlength="100" value="${esc(old.sku || generatedSku)}" required></td>
                <td><input class="form-control form-control-sm variant-price" type="number" min="0" step="1" placeholder="Dùng giá chung" value="${esc(old.price ?? '')}"></td>
                <td><input class="form-control form-control-sm variant-stock" type="number" min="0" max="2147483647" step="1" value="${esc(old.stock ?? 0)}" required></td>`;
            const hidden = document.createElement('div');
            hidden.className = 'variant-hidden-fields';
            const addHidden = (name, value) => { const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; hidden.appendChild(input); };
            if (old.id) addHidden(`variants[${index}][id]`, old.id);
            addHidden(`variants[${index}][attributes]`, JSON.stringify(attrs));
            row.querySelector('.variant-sku').name = `variants[${index}][sku]`;
            row.querySelector('.variant-price').name = `variants[${index}][price]`;
            row.querySelector('.variant-stock').name = `variants[${index}][stock]`;
            row.querySelector('td').appendChild(hidden);
            tableBody.appendChild(row);
        });
        tableWrap.hidden = false;
        alertBox.textContent = 'Đã tạo ' + combinations.length + ' biến thể. Tồn kho sản phẩm sẽ bằng tổng tồn kho của các biến thể.';
        setStockMode(true);
    };

    document.getElementById('add-attribute').addEventListener('click', () => addGroup());
    document.getElementById('generate-variants').addEventListener('click', () => renderVariants(true));
    groupContainer.addEventListener('click', event => {
        const button = event.target.closest('.remove-attribute');
        if (button) button.closest('.attribute-group').remove();
    });
    skuInput.addEventListener('change', () => {
        // Keep manually adjusted SKUs intact; new combinations get a fresh generated code.
    });
    form.addEventListener('submit', event => {
        const hasAttributes = currentGroups().length > 0;
        renderVariants(true);
        if (hasAttributes && tableWrap.hidden) {
            event.preventDefault();
            alert('Vui lòng kiểm tra lại các thuộc tính và tổ hợp biến thể.');
        }
    });

    // Restore edit/validation state and construct the variant table on first load.
    if (currentGroups().length) renderVariants(true);

    const imageInput = document.getElementById('hinh_anh');
    if (imageInput) {
        imageInput.addEventListener('change', function () {
            const label = imageInput.nextElementSibling;
            const count = imageInput.files.length;
            label.textContent = count === 0 ? 'Chọn một hoặc nhiều ảnh' : (count === 1 ? imageInput.files[0].name : count + ' ảnh đã chọn');
        });
    }
})();
</script>
@endsection
