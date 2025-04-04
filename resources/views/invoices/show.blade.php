@extends('layouts.app')

@section('title', 'تفاصيل الفاتورة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تفاصيل الفاتورة #{{ $invoice->invoice_number }}</h2>
        <div>
            @if($invoice->status != 'cancelled' && $invoice->status != 'draft')
                <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-dark me-2" target="_blank">
                    <i class="bi bi-printer"></i> طباعة الفاتورة
                </a>
            @endif
            @if($invoice->status != 'cancelled' && $invoice->status != 'paid')
                <a href="{{ route('invoices.payment', $invoice) }}" class="btn btn-success me-2">
                    <i class="bi bi-cash"></i> تسجيل دفعة
                </a>
            @endif
            @if($invoice->status == 'draft')
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning me-2">
                    <i class="bi bi-pencil"></i> تعديل الفاتورة
                </a>
                <a href="{{ route('invoices.issue', $invoice) }}" class="btn btn-primary me-2"
                    onclick="return confirm('هل أنت متأكد من إصدار هذه الفاتورة؟')">
                    <i class="bi bi-check-circle"></i> إصدار الفاتورة
                </a>
            @endif
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> العودة للفواتير
            </a>
        </div>
    </div>

    <div class="row">
        <!-- بيانات الفاتورة والإجمالي -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بيانات الفاتورة</h5>
                    <span class="badge bg-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partially_paid' ? 'warning' : ($invoice->status == 'overdue' ? 'danger' : ($invoice->status == 'issued' ? 'info' : ($invoice->status == 'cancelled' ? 'dark' : 'secondary')))) }} fs-6">
                        @if($invoice->status == 'paid')
                            مدفوعة
                        @elseif($invoice->status == 'partially_paid')
                            مدفوعة جزئياً
                        @elseif($invoice->status == 'overdue')
                            متأخرة
                        @elseif($invoice->status == 'issued')
                            صادرة
                        @elseif($invoice->status == 'draft')
                            مسودة
                        @elseif($invoice->status == 'cancelled')
                            ملغية
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">معلومات الفاتورة</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>رقم الفاتورة:</strong></td>
                                    <td>{{ $invoice->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>تاريخ الإصدار:</strong></td>
                                    <td>{{ $invoice->issue_date }}</td>
                                </tr>
                                <tr>
                                    <td><strong>تاريخ الاستحقاق:</strong></td>
                                    <td>{{ $invoice->due_date }}</td>
                                </tr>
                                <tr>
                                    <td><strong>نوع الفاتورة:</strong></td>
                                    <td>
                                        @if($invoice->type == 'sale')
                                            <span class="badge bg-primary">بيع</span>
                                        @elseif($invoice->type == 'purchase')
                                            <span class="badge bg-secondary">شراء</span>
                                        @elseif($invoice->type == 'shipping')
                                            <span class="badge bg-info">شحن</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($invoice->reference_number)
                                    <tr>
                                        <td><strong>الرقم المرجعي:</strong></td>
                                        <td>{{ $invoice->reference_number }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">معلومات الحساب</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $invoice->account->name }}</h6>
                                    <p class="card-text mb-1">رقم الحساب: {{ $invoice->account->account_number }}</p>
                                    @if($invoice->account->phone)
                                        <p class="card-text mb-1">هاتف: {{ $invoice->account->phone }}</p>
                                    @endif
                                    @if($invoice->account->email)
                                        <p class="card-text mb-1">بريد إلكتروني: {{ $invoice->account->email }}</p>
                                    @endif
                                    @if($invoice->account->address)
                                        <p class="card-text mb-0">
                                            العنوان: {{ $invoice->account->address }}
                                            @if($invoice->account->city)
                                                ، {{ $invoice->account->city }}
                                            @endif
                                            @if($invoice->account->country)
                                                ، {{ $invoice->account->country }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($invoice->car)
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">معلومات السيارة</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>الماركة/الموديل:</strong> {{ $invoice->car->make }} {{ $invoice->car->model }}</p>
                                            <p class="mb-1"><strong>سنة الصنع:</strong> {{ $invoice->car->year }}</p>
                                            <p class="mb-0"><strong>رقم الهيكل (VIN):</strong> {{ $invoice->car->vin }}</p>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <a href="{{ route('cars.show', $invoice->car) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> عرض تفاصيل السيارة
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">بنود الفاتورة</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الوصف</th>
                                        <th class="text-center">الكمية</th>
                                        <th class="text-end">سعر الوحدة</th>
                                        <th class="text-end">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">{{ number_format($item->price, 2) }} $</td>
                                            <td class="text-end">{{ number_format($item->total, 2) }} $</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-end"><strong>المجموع الفرعي:</strong></td>
                                        <td class="text-end">{{ number_format($invoice->subtotal, 2) }} $</td>
                                    </tr>
                                    @if($invoice->discount > 0)
                                        <tr>
                                            <td colspan="3"></td>
                                            <td class="text-end"><strong>الخصم:</strong></td>
                                            <td class="text-end">{{ number_format($invoice->discount, 2) }} $</td>
                                        </tr>
                                    @endif
                                    @if($invoice->tax > 0)
                                        <tr>
                                            <td colspan="3"></td>
                                            <td class="text-end"><strong>الضريبة:</strong></td>
                                            <td class="text-end">{{ number_format($invoice->tax, 2) }} $</td>
                                        </tr>
                                    @endif
                                    @if($invoice->shipping_fee > 0)
                                        <tr>
                                            <td colspan="3"></td>
                                            <td class="text-end"><strong>رسوم الشحن:</strong></td>
                                            <td class="text-end">{{ number_format($invoice->shipping_fee, 2) }} $</td>
                                        </tr>
                                    @endif
                                    <tr class="table-primary">
                                        <td colspan="3"></td>
                                        <td class="text-end"><strong>الإجمالي:</strong></td>
                                        <td class="text-end"><strong>{{ number_format($invoice->total_amount, 2) }} $</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    @if($invoice->notes)
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">ملاحظات</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    {{ $invoice->notes }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- إجماليات الفاتورة والدفعات -->
        <div class="col-md-4">
            <!-- ملخص الدفع -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">ملخص الدفع</h5>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 20px;">
                        @php
                            $paymentPercentage = ($invoice->paid_amount / $invoice->total_amount) * 100;
                        @endphp
                        <div class="progress-bar bg-success" role="progressbar"
                             style="width: {{ $paymentPercentage }}%;"
                             aria-valuenow="{{ $paymentPercentage }}" aria-valuemin="0" aria-valuemax="100">
                            {{ number_format($paymentPercentage, 0) }}%
                        </div>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>إجمالي الفاتورة:</span>
                            <span class="fw-bold">{{ number_format($invoice->total_amount, 2) }} $</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>المبلغ المدفوع:</span>
                            <span class="text-success fw-bold">{{ number_format($invoice->paid_amount, 2) }} $</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>المبلغ المتبقي:</span>
                            <span class="text-{{ ($invoice->total_amount - $invoice->paid_amount) > 0 ? 'danger' : 'success' }} fw-bold">
                                {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }} $
                            </span>
                        </li>
                    </ul>
                    <div class="text-center mt-3">
                        @if($invoice->status != 'cancelled' && $invoice->status != 'paid' && ($invoice->total_amount - $invoice->paid_amount) > 0)
                            <a href="{{ route('invoices.payment', $invoice) }}" class="btn btn-success w-100">
                                <i class="bi bi-cash"></i> تسجيل دفعة
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- سجل الدفعات -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">سجل الدفعات</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>المبلغ</th>
                                    <th>الطريقة</th>
                                    <th>المرجع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date }}</td>
                                        <td>{{ number_format($payment->amount, 2) }} $</td>
                                        <td>
                                            @if($payment->payment_method == 'cash')
                                                <span class="badge bg-success">نقداً</span>
                                            @elseif($payment->payment_method == 'bank_transfer')
                                                <span class="badge bg-primary">تحويل بنكي</span>
                                            @elseif($payment->payment_method == 'credit_card')
                                                <span class="badge bg-info">بطاقة ائتمان</span>
                                            @elseif($payment->payment_method == 'check')
                                                <span class="badge bg-warning">شيك</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('transactions.show', $payment->transaction) }}">
                                                {{ $payment->transaction->transaction_number }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">لا توجد دفعات مسجلة</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- الإجراءات -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">الإجراءات</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($invoice->status == 'draft')
                            <a href="{{ route('invoices.issue', $invoice) }}" class="btn btn-primary"
                                onclick="return confirm('هل أنت متأكد من إصدار هذه الفاتورة؟')">
                                <i class="bi bi-check-circle"></i> إصدار الفاتورة
                            </a>
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> تعديل الفاتورة
                            </a>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete('{{ $invoice->id }}')">
                                <i class="bi bi-trash"></i> حذف الفاتورة
                            </button>
                            <form id="delete-form-{{ $invoice->id }}" action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                        @if($invoice->status != 'cancelled' && $invoice->status != 'draft')
                            <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-dark" target="_blank">
                                <i class="bi bi-printer"></i> طباعة الفاتورة
                            </a>
                            @if($invoice->account->email)
                                <a href="{{ route('invoices.send', $invoice) }}" class="btn btn-info"
                                    onclick="return confirm('هل تريد إرسال الفاتورة بالبريد الإلكتروني إلى {{ $invoice->account->email }}؟')">
                                    <i class="bi bi-envelope"></i> إرسال بالبريد الإلكتروني
                                </a>
                            @endif
                        @endif
                        @if($invoice->status != 'cancelled' && $invoice->status != 'draft')
                            <button type="button" class="btn btn-secondary" onclick="confirmCancel('{{ $invoice->id }}')">
                                <i class="bi bi-x-circle"></i> إلغاء الفاتورة
                            </button>
                            <form id="cancel-form-{{ $invoice->id }}" action="{{ route('invoices.cancel', $invoice) }}" method="POST" class="d-none">
                                @csrf
                                @method('PATCH')
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete(id) {
        if (confirm('هل أنت متأكد من حذف هذه الفاتورة؟')) {
            document.getElementById('delete-form-' + id).submit();
        }
    }

    function confirmCancel(id) {
        if (confirm('هل أنت متأكد من إلغاء هذه الفاتورة؟')) {
            document.getElementById('cancel-form-' + id).submit();
        }
    }
</script>
@endpush
