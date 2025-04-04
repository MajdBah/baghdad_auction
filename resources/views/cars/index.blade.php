@extends('layouts.app')

@section('title', 'إدارة السيارات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة السيارات</h2>
        <a href="{{ route('cars.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> إضافة سيارة جديدة
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == '' || request('status') == 'all' ? 'active' : '' }}"
                               href="{{ route('cars.index', ['status' => 'all']) }}">
                                الكل
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'in_auction' ? 'active' : '' }}"
                               href="{{ route('cars.index', ['status' => 'in_auction']) }}">
                                بالمزاد
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'purchased' ? 'active' : '' }}"
                               href="{{ route('cars.index', ['status' => 'purchased']) }}">
                                تم الشراء
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'shipping' ? 'active' : '' }}"
                               href="{{ route('cars.index', ['status' => 'shipping']) }}">
                                قيد الشحن
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'delivered' ? 'active' : '' }}"
                               href="{{ route('cars.index', ['status' => 'delivered']) }}">
                                تم التسليم
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('cars.index') }}" method="GET" class="d-flex">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <input type="text" name="search" class="form-control" placeholder="بحث..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم السيارة</th>
                            <th>الماركة / الموديل</th>
                            <th>سنة الصنع</th>
                            <th>VIN</th>
                            <th>العميل</th>
                            <th>شركة الشحن</th>
                            <th>سعر الشراء</th>
                            <th>تكلفة الشحن</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cars as $car)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $car->car_number }}</td>
                                <td>{{ $car->make }} {{ $car->model }}</td>
                                <td>{{ $car->year }}</td>
                                <td>{{ $car->vin }}</td>
                                <td>
                                    @if($car->customerAccount)
                                        <a href="{{ route('accounts.show', $car->customerAccount) }}">
                                            {{ $car->customerAccount->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($car->shippingCompany)
                                        <a href="{{ route('accounts.show', $car->shippingCompany) }}">
                                            {{ $car->shippingCompany->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ number_format($car->purchase_price, 2) }}</td>
                                <td>{{ number_format($car->shipping_cost, 2) }}</td>
                                <td>
                                    @if($car->status == 'in_auction')
                                        <span class="badge bg-warning">بالمزاد</span>
                                    @elseif($car->status == 'purchased')
                                        <span class="badge bg-info">تم الشراء</span>
                                    @elseif($car->status == 'shipping')
                                        <span class="badge bg-primary">قيد الشحن</span>
                                    @elseif($car->status == 'delivered')
                                        <span class="badge bg-success">تم التسليم</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('cars.show', $car) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('cars.edit', $car) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete('{{ $car->id }}', '{{ $car->make }} {{ $car->model }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $car->id }}"
                                              action="{{ route('cars.destroy', $car) }}"
                                              method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">لا توجد سيارات لعرضها</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $cars->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        if (confirm(`هل أنت متأكد من حذف السيارة "${name}"؟`)) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
</script>
@endpush
