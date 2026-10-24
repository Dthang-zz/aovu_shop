@extends('layouts.admin')

@section('title', $page['title'])
@section('page-heading', $page['title'])
@section('page-subheading', $page['subtitle'])
@section('breadcrumb', $page['title'])

@section('content')
<div class="row">
    @foreach ($page['stats'] as $stat)
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="small-box bg-white">
                <div class="inner">
                    <p class="text-muted mb-1">{{ $stat[0] }}</p>
                    <h3>{{ $stat[1] }}</h3>
                </div>
                <div class="icon text-{{ $stat[3] }}"><i class="{{ $stat[2] }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-5">
                <h3 class="card-title"><i class="{{ $page['icon'] }} text-primary mr-2"></i>Danh sách {{ mb_strtolower($page['title']) }}</h3>
                <div class="small text-muted mt-1">Dữ liệu minh họa cho giao diện quản trị</div>
            </div>
            <div class="col-md-7 mt-3 mt-md-0">
                <div class="d-flex justify-content-md-end">
                    <div class="input-group input-group-sm mr-2" style="max-width: 240px">
                        <input type="search" class="form-control" placeholder="Tìm kiếm..." aria-label="Tìm kiếm">
                        <div class="input-group-append"><button class="btn btn-default" type="button" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm text-nowrap"><i class="fas fa-plus mr-1"></i>{{ $page['action'] }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th class="pl-3">{{ $page['recordLabel'] }}</th>
                    @foreach ($page['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                    <th>Trạng thái</th>
                    <th class="text-right pr-3">Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($page['rows'] as $row)
                    <tr>
                        <td class="pl-3">
                            <div class="d-flex align-items-center">
                                @if (isset($row['image']))
                                    <img class="product-thumb mr-2" src="{{ asset('adminlte/img/'.$row['image']) }}" alt="">
                                @endif
                                <div>
                                    <div class="font-weight-bold text-dark">{{ $row['title'] }}</div>
                                    <small class="text-muted">{{ $row['meta'] }}</small>
                                </div>
                            </div>
                        </td>
                        @foreach ($row['cells'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                        <td><span class="badge badge-{{ $row['status'][1] }} px-2 py-1">{{ $row['status'][0] }}</span></td>
                        <td class="text-right pr-3 table-actions text-nowrap">
                            <button type="button" class="btn btn-default btn-sm text-primary" aria-label="Xem chi tiết"><i class="far fa-eye"></i></button>
                            <button type="button" class="btn btn-default btn-sm text-muted" aria-label="Chỉnh sửa"><i class="far fa-edit"></i></button>
                            <button type="button" class="btn btn-default btn-sm text-danger" aria-label="Xóa"><i class="far fa-trash-alt"></i></button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ count($page['rows']) }} trên tổng số {{ $page['stats'][0][1] }} mục <span class="text-muted">(dữ liệu mẫu)</span></span>
        <nav aria-label="Phân trang minh họa">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#" aria-label="Trang trước">&laquo;</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#" aria-label="Trang sau">&raquo;</a></li>
            </ul>
        </nav>
    </div>
</div>
@endsection
