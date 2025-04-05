@extends('layouts.app')

@section('content')
<div class="container" dir="rtl" lang="ar">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file-invoice ms-2"></i>تفاصيل الفاتورة #{{ $invoice->invoice_number }}</h5>
                        <span class="badge bg-light text-dark">{{ $invoice->isPositiveForBroker() ? 'فاتورة موجبة (إيراد)' : 'فاتورة سالبة (مصروف)' }}</span>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert {{ $invoice->isPositiveForBroker() ? 'alert-success' : 'alert-danger' }}">
                                <h5 class="alert-heading">
                                    <i class="fas {{ $invoice->isPositiveForBroker() ? 'fa-arrow-circle-left' : 'fa-arrow-circle-right' }} ms-2"></i>
                                    {{ $invoice->isPositiveForBroker() ? 'فاتورة موجبة (من العميل إلى الوسيط)' : 'فاتورة سالبة (من الوسيط إلى شركة الشحن)' }}
                                </h5>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <strong>من حساب:</strong> {{ $invoice->fromAccount->name }} ({{ $invoice->fromAccount->account_number }})
                                    </div>
                                    <div class="col-md-6">
                                        <strong>إلى حساب:</strong> {{ $invoice->toAccount->name }} ({{ $invoice->toAccount->account_number }})
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">معلومات الفاتورة</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <strong>رقم الفاتورة:</strong>
                                            <div>{{ $invoice->invoice_number }}</div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>تاريخ الإصدار:</strong>
                                            <div>{{ $invoice->issue_date->format('Y-m-d') }}</div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>تاريخ الاستحقاق:</strong>
                                            <div>{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'غير محدد' }}</div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>الحالة:</strong>
                                            <div>
                                                <span class="badge {{ $invoice->isPaid() ? 'bg-success' : ($invoice->isPartiallyPaid() ? 'bg-warning' : 'bg-secondary') }}">
                                                    {{ $invoice->isPaid() ? 'مدفوعة' : ($invoice->isPartiallyPaid() ? 'مدفوعة جزئياً' : 'غير مدفوعة') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">المبالغ والمدفوعات</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6 class="card-title">المبلغ الإجمالي</h6>
                                                    <h3 class="card-subtitle mb-2 text-muted">{{ number_format($invoice->total_amount, 2) }} د.ع</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6 class="card-title">المبلغ المدفوع</h6>
                                                    <h3 class="card-subtitle mb-2 text-success">{{ number_format($invoice->paid_amount, 2) }} د.ع</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6 class="card-title">الرصيد المتبقي</h6>
                                                    <h3 class="card-subtitle mb-2 {{ $invoice->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($invoice->balance, 2) }} د.ع</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <strong>المبلغ الفرعي:</strong>
                                            <div>{{ number_format($invoice->subtotal, 2) }} د.ع</div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>الضريبة ({{ $invoice->tax_rate }}%):</strong>
                                            <div>{{ number_format($invoice->tax_amount, 2) }} د.ع</div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>رسوم الشحن:</strong>
                                            <div>{{ number_format($invoice->shipping_fee, 2) }} د.ع</div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>الخصم:</strong>
                                            <div>{{ number_format($invoice->discount, 2) }} د.ع</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($invoice->notes)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">ملاحظات</h6>
                                    </div>
                                    <div class="card-body">
                                        {{ $invoice->notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($invoice->payments->count() > 0)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">المدفوعات ({{ $invoice->payments->count() }})</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>رقم المعاملة</th>
                                                        <th>التاريخ</th>
                                                        <th>المبلغ</th>
                                                        <th>طريقة الدفع</th>
                                                        <th>الحالة</th>
                                                        <th>ملاحظات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($invoice->payments as $payment)
                                                        <tr>
                                                            <td>{{ $payment->transaction_number }}</td>
                                                            <td>{{ $payment->transaction_date ? $payment->transaction_date->format('Y-m-d') : '-' }}</td>
                                                            <td>{{ number_format($payment->amount, 2) }} د.ع</td>
                                                            <td>{{ $payment->payment_method ?: '-' }}</td>
                                                            <td>
                                                                <span class="badge {{ $payment->status == 'completed' ? 'bg-success' : 'bg-warning' }}">
                                                                    {{ $payment->status == 'completed' ? 'مكتملة' : $payment->status }}
                                                                </span>
                                                            </td>
                                                            <td>{{ $payment->description ?: '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-primary">
                                                    <tr>
                                                        <th colspan="2" class="text-start">المجموع</th>
                                                        <th>{{ number_format($invoice->payments->sum('amount'), 2) }} د.ع</th>
                                                        <th colspan="3"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('broker.invoices.list', ['broker_account_id' => $invoice->isPositiveForBroker() ? $invoice->toAccount->id : $invoice->fromAccount->id]) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right ms-1"></i>العودة للقائمة
                        </a>

                        <div>
                            @if(!$invoice->isPaid())
                                <a href="{{ route('invoices.payment_form', $invoice->id) }}" class="btn btn-success">
                                    <i class="fas fa-money-bill-wave ms-1"></i>تسجيل دفعة
                                </a>
                            @endif

                            <a href="{{ route('invoices.print', $invoice->id) }}" class="btn btn-info" target="_blank">
                                <i class="fas fa-print ms-1"></i>طباعة
                            </a>

                            <a href="{{ route('invoices.pdf', $invoice->id) }}" class="btn btn-primary">
                                <i class="fas fa-file-pdf ms-1"></i>تحميل PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
