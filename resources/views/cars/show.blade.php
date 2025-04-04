@extends('layouts.app')

@section('title', 'تفاصيل السيارة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تفاصيل السيارة: {{ $car->make }} {{ $car->model }} ({{ $car->year }})</h2>
        <div>
            <a href="{{ route('cars.edit', $car) }}" class="btn btn-warning me-2">
                <i class="bi bi-pencil"></i> تعديل السيارة
            </a>
            <a href="{{ route('cars.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> العودة للسيارات
            </a>
        </div>
    </div>

    <div class="row">
        <!-- معلومات السيارة -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">معلومات السيارة</h5>
                    <span class="badge bg-{{ $car->status == 'in_auction' ? 'warning' : ($car->status == 'purchased' ? 'info' : ($car->status == 'shipping' ? 'primary' : 'success')) }}">
                        @if($car->status == 'in_auction')
                            بالمزاد
                        @elseif($car->status == 'purchased')
                            تم الشراء
                        @elseif($car->status == 'shipping')
                            قيد الشحن
                        @elseif($car->status == 'delivered')
                            تم التسليم
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        @if($car->images && count($car->images) > 0)
                            <div id="carImagesCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    @foreach(json_decode($car->images) as $index => $image)
                                        <div class="carousel-item {{ $index == 0 ? 'active' : '' }}">
                                            <img src="{{ asset('storage/' . $image) }}" class="d-block w-100 rounded" alt="{{ $car->make }} {{ $car->model }}">
                                        </div>
                                    @endforeach
                                </div>
                                @if(count(json_decode($car->images)) > 1)
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carImagesCarousel" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">السابق</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carImagesCarousel" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">التالي</span>
                                    </button>
                                @endif
                            </div>
                        @else
                            <img src="{{ asset('images/car-placeholder.jpg') }}" class="img-fluid rounded" alt="{{ $car->make }} {{ $car->model }}">
                        @endif
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>رقم السيارة:</strong></span>
                            <span>{{ $car->car_number }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>الماركة:</strong></span>
                            <span>{{ $car->make }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>الموديل:</strong></span>
                            <span>{{ $car->model }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>سنة الصنع:</strong></span>
                            <span>{{ $car->year }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>اللون:</strong></span>
                            <span>{{ $car->color ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>رقم الهيكل (VIN):</strong></span>
                            <span class="text-monospace">{{ $car->vin }}</span>
                        </li>
                        @if($car->auction_name)
                            <li class="list-group-item d-flex justify-content-between">
                                <span><strong>المزاد:</strong></span>
                                <span>{{ $car->auction_name }}</span>
                            </li>
                        @endif
                        @if($car->description)
                            <li class="list-group-item">
                                <strong>وصف السيارة:</strong>
                                <p class="mt-2 mb-0">{{ $car->description }}</p>
                            </li>
                        @endif
                        @if($car->notes)
                            <li class="list-group-item">
                                <strong>ملاحظات:</strong>
                                <p class="mt-2 mb-0">{{ $car->notes }}</p>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('{{ $car->id }}', '{{ $car->make }} {{ $car->model }}')">
                        <i class="bi bi-trash"></i> حذف السيارة
                    </button>
                    <span class="text-muted">تم الإضافة: {{ $car->created_at->format('Y-m-d') }}</span>
                    <form id="delete-form-{{ $car->id }}" action="{{ route('cars.destroy', $car) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>

        <!-- معلومات العملية والشحن -->
        <div class="col-md-7 mb-4">
            <div class="row">
                <!-- بيانات العميل والشحن -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">بيانات العميل وشركة الشحن</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">العميل</h6>
                                        </div>
                                        <div class="card-body">
                                            @if($car->customerAccount)
                                                <h5>
                                                    <a href="{{ route('accounts.show', $car->customerAccount) }}" class="text-decoration-none">
                                                        {{ $car->customerAccount->name }}
                                                    </a>
                                                </h5>
                                                <p class="mb-0">رقم الحساب: {{ $car->customerAccount->account_number }}</p>
                                                @if($car->customerAccount->phone)
                                                    <p class="mb-0">هاتف: {{ $car->customerAccount->phone }}</p>
                                                @endif
                                                @if($car->customerAccount->email)
                                                    <p class="mb-0">بريد إلكتروني: {{ $car->customerAccount->email }}</p>
                                                @endif
                                            @else
                                                <p class="text-center mb-0">لم يتم تحديد عميل</p>
                                                <div class="text-center mt-3">
                                                    <a href="{{ route('cars.edit', $car) }}?add_customer=1" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-person-plus"></i> إضافة عميل
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">شركة الشحن</h6>
                                        </div>
                                        <div class="card-body">
                                            @if($car->shippingCompany)
                                                <h5>
                                                    <a href="{{ route('accounts.show', $car->shippingCompany) }}" class="text-decoration-none">
                                                        {{ $car->shippingCompany->name }}
                                                    </a>
                                                </h5>
                                                <p class="mb-0">رقم الحساب: {{ $car->shippingCompany->account_number }}</p>
                                                @if($car->shippingCompany->phone)
                                                    <p class="mb-0">هاتف: {{ $car->shippingCompany->phone }}</p>
                                                @endif
                                                @if($car->shippingCompany->email)
                                                    <p class="mb-0">بريد إلكتروني: {{ $car->shippingCompany->email }}</p>
                                                @endif
                                            @else
                                                <p class="text-center mb-0">لم يتم تحديد شركة شحن</p>
                                                @if($car->status == 'shipping' || $car->status == 'delivered')
                                                    <div class="text-center mt-3">
                                                        <a href="{{ route('cars.edit', $car) }}?add_shipping=1" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-building"></i> إضافة شركة شحن
                                                        </a>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات المالية -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">المعلومات المالية والتواريخ</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">التكاليف</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span><strong>سعر الشراء:</strong></span>
                                                    <span class="fw-bold">{{ number_format($car->purchase_price, 2) }} $</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span><strong>تكلفة الشحن:</strong></span>
                                                    <span class="fw-bold">{{ number_format($car->shipping_cost, 2) }} $</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between bg-light">
                                                    <span><strong>إجمالي التكلفة:</strong></span>
                                                    <span class="fw-bold">{{ number_format($car->purchase_price + $car->shipping_cost, 2) }} $</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-secondary text-white">
                                            <h6 class="mb-0">التواريخ</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span><strong>تاريخ الشراء:</strong></span>
                                                    <span>{{ $car->purchase_date ?? 'غير محدد' }}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span><strong>تاريخ الشحن:</strong></span>
                                                    <span>{{ $car->shipping_date ?? 'غير محدد' }}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span><strong>تاريخ التسليم المتوقع:</strong></span>
                                                    <span>{{ $car->delivery_date ?? 'غير محدد' }}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span><strong>تاريخ التسليم الفعلي:</strong></span>
                                                    <span>{{ $car->actual_delivery_date ?? 'غير محدد' }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- المعاملات المرتبطة -->
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">المعاملات المالية المرتبطة</h5>
                            <a href="{{ route('transactions.create', ['car_id' => $car->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-circle"></i> إضافة معاملة
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>رقم المعاملة</th>
                                            <th>النوع</th>
                                            <th>من</th>
                                            <th>إلى</th>
                                            <th>المبلغ</th>
                                            <th>التاريخ</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($car->transactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->transaction_number }}</td>
                                                <td>
                                                    @if($transaction->type == 'purchase')
                                                        <span class="badge bg-secondary">شراء</span>
                                                    @elseif($transaction->type == 'shipping')
                                                        <span class="badge bg-info">شحن</span>
                                                    @elseif($transaction->type == 'transfer')
                                                        <span class="badge bg-primary">تحويل</span>
                                                    @elseif($transaction->type == 'payment')
                                                        <span class="badge bg-warning">دفعة</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($transaction->fromAccount)
                                                        <a href="{{ route('accounts.show', $transaction->fromAccount) }}">
                                                            {{ $transaction->fromAccount->name }}
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($transaction->toAccount)
                                                        <a href="{{ route('accounts.show', $transaction->toAccount) }}">
                                                            {{ $transaction->toAccount->name }}
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ number_format($transaction->amount, 2) }}</td>
                                                <td>{{ $transaction->transaction_date }}</td>
                                                <td>
                                                    @if($transaction->status == 'completed')
                                                        <span class="badge bg-success">مكتملة</span>
                                                    @elseif($transaction->status == 'pending')
                                                        <span class="badge bg-warning">معلقة</span>
                                                    @elseif($transaction->status == 'cancelled')
                                                        <span class="badge bg-danger">ملغية</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">لا توجد معاملات مرتبطة بهذه السيارة</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الفواتير المرتبطة -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">الفواتير المرتبطة</h5>
                            <a href="{{ route('invoices.create', ['car_id' => $car->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-circle"></i> إنشاء فاتورة
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>رقم الفاتورة</th>
                                            <th>الحساب</th>
                                            <th>النوع</th>
                                            <th>المبلغ</th>
                                            <th>المدفوع</th>
                                            <th>المتبقي</th>
                                            <th>تاريخ الإصدار</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($car->invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>
                                                    <a href="{{ route('accounts.show', $invoice->account) }}">
                                                        {{ $invoice->account->name }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($invoice->type == 'sale')
                                                        <span class="badge bg-primary">بيع</span>
                                                    @elseif($invoice->type == 'purchase')
                                                        <span class="badge bg-secondary">شراء</span>
                                                    @elseif($invoice->type == 'shipping')
                                                        <span class="badge bg-info">شحن</span>
                                                    @endif
                                                </td>
                                                <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                                <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                                                <td>{{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                                                <td>{{ $invoice->issue_date }}</td>
                                                <td>
                                                    @if($invoice->status == 'paid')
                                                        <span class="badge bg-success">مدفوعة</span>
                                                    @elseif($invoice->status == 'partially_paid')
                                                        <span class="badge bg-warning">مدفوعة جزئياً</span>
                                                    @elseif($invoice->status == 'overdue')
                                                        <span class="badge bg-danger">متأخرة</span>
                                                    @elseif($invoice->status == 'issued')
                                                        <span class="badge bg-info">صادرة</span>
                                                    @elseif($invoice->status == 'draft')
                                                        <span class="badge bg-secondary">مسودة</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">لا توجد فواتير مرتبطة بهذه السيارة</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
