@extends('layouts.app')

@section('title', 'تفاصيل العملية المالية')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تفاصيل العملية المالية</h2>
        <div>
            @if($transaction->status == 'pending')
                <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-warning me-2">
                    <i class="bi bi-pencil"></i> تعديل
                </a>
                <button type="button" class="btn btn-success me-2" onclick="confirmProcess('{{ $transaction->id }}')">
                    <i class="bi bi-check-circle"></i> تنفيذ العملية
                </button>
                <button type="button" class="btn btn-danger me-2" onclick="confirmCancel('{{ $transaction->id }}')">
                    <i class="bi bi-x-circle"></i> إلغاء
                </button>
            @endif
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> العودة للعمليات المالية
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">معلومات العملية المالية</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%;">رقم العملية:</th>
                                    <td><strong>{{ $transaction->transaction_number }}</strong></td>
                                </tr>
                                <tr>
                                    <th>نوع العملية:</th>
                                    <td>
                                        @if($transaction->type == 'purchase')
                                            <span class="badge bg-secondary">شراء</span>
                                        @elseif($transaction->type == 'shipping')
                                            <span class="badge bg-info">شحن</span>
                                        @elseif($transaction->type == 'transfer')
                                            <span class="badge bg-primary">تحويل</span>
                                        @elseif($transaction->type == 'payment')
                                            <span class="badge bg-warning">دفعة</span>
                                        @elseif($transaction->type == 'commission')
                                            <span class="badge bg-dark">عمولة</span>
                                        @elseif($transaction->type == 'refund')
                                            <span class="badge bg-success">استرداد</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>تاريخ العملية:</th>
                                    <td>{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                                </tr>
                                <tr>
                                    <th>الرقم المرجعي:</th>
                                    <td>{{ $transaction->reference_number ?: 'غير محدد' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%;">الحالة:</th>
                                    <td>
                                        @if($transaction->status == 'completed')
                                            <span class="badge bg-success">مكتملة</span>
                                        @elseif($transaction->status == 'pending')
                                            <span class="badge bg-warning">معلقة</span>
                                        @elseif($transaction->status == 'cancelled')
                                            <span class="badge bg-danger">ملغية</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>تاريخ الإنشاء:</th>
                                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>آخر تحديث:</th>
                                    <td>{{ $transaction->updated_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>منشئ العملية:</th>
                                    <td>{{ $transaction->createdBy->name ?? 'غير محدد' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>تفاصيل المبالغ</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th style="width: 40%;">المبلغ الأساسي:</th>
                                        <td><strong>{{ number_format($transaction->amount, 2) }} $</strong></td>
                                    </tr>
                                    @if($transaction->with_commission)
                                    <tr>
                                        <th>العمولة:</th>
                                        <td><span class="text-danger">{{ number_format($transaction->commission_amount, 2) }} $</span></td>
                                    </tr>
                                    <tr>
                                        <th>المبلغ الإجمالي:</th>
                                        <td><strong>{{ number_format($transaction->amount + $transaction->commission_amount, 2) }} $</strong></td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>الحسابات المعنية</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th style="width: 40%;">من حساب:</th>
                                        <td>
                                            @if($transaction->fromAccount)
                                                <a href="{{ route('accounts.show', $transaction->fromAccount) }}">
                                                    {{ $transaction->fromAccount->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>إلى حساب:</th>
                                        <td>
                                            @if($transaction->toAccount)
                                                <a href="{{ route('accounts.show', $transaction->toAccount) }}">
                                                    {{ $transaction->toAccount->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($transaction->car)
                    <div class="border-top pt-3 mt-3">
                        <h6>السيارة المرتبطة</h6>
                        <div class="card mb-0 mt-2">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>{{ $transaction->car->make }} {{ $transaction->car->model }} ({{ $transaction->car->year }})</h6>
                                        <p class="text-muted mb-0">VIN: {{ $transaction->car->vin }}</p>
                                    </div>
                                    <div>
                                        <a href="{{ route('cars.show', $transaction->car) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> عرض السيارة
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($transaction->description)
                    <div class="border-top pt-3 mt-3">
                        <h6>الوصف</h6>
                        <p>{{ $transaction->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Transaction Status Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">حالة العملية</h5>
                </div>
                <div class="card-body">
                    @if($transaction->status == 'completed')
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i>
                            <strong>هذه العملية مكتملة</strong>
                            <p class="mb-0 mt-2">تم تنفيذ هذه العملية المالية وتحديث أرصدة الحسابات المعنية.</p>
                        </div>
                    @elseif($transaction->status == 'pending')
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>هذه العملية معلقة</strong>
                            <p class="mb-0 mt-2">لم يتم تنفيذ هذه العملية المالية بعد. يجب تنفيذها لتحديث أرصدة الحسابات المعنية.</p>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            <button type="button" class="btn btn-success" onclick="confirmProcess('{{ $transaction->id }}')">
                                <i class="bi bi-check-circle"></i> تنفيذ العملية
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmCancel('{{ $transaction->id }}')">
                                <i class="bi bi-x-circle"></i> إلغاء العملية
                            </button>
                        </div>
                    @elseif($transaction->status == 'cancelled')
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle-fill"></i>
                            <strong>هذه العملية ملغية</strong>
                            <p class="mb-0 mt-2">تم إلغاء هذه العملية المالية ولن تؤثر على أرصدة الحسابات.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Related Records Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">العمليات المرتبطة</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @if($transaction->reference_number)
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-link"></i> فاتورة مرتبطة
                                </div>
                                <span class="badge bg-primary rounded-pill">#{{ $transaction->reference_number }}</span>
                            </a>
                        @endif
                        <div class="list-group-item">
                            <small class="text-muted">
                                لا توجد عناصر مرتبطة إضافية لعرضها.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Forms for Confirm Actions -->
<form id="process-form" action="{{ route('transactions.process', $transaction) }}" method="POST" class="d-none">
    @csrf
    @method('PATCH')
</form>

<form id="cancel-form" action="{{ route('transactions.cancel', $transaction) }}" method="POST" class="d-none">
    @csrf
    @method('PATCH')
</form>
@endsection

@push('scripts')
<script>
    function confirmProcess(id) {
        if (confirm('هل أنت متأكد من تنفيذ هذه المعاملة؟ سيتم تعديل أرصدة الحسابات ذات الصلة.')) {
            document.getElementById('process-form').submit();
        }
    }

    function confirmCancel(id) {
        if (confirm('هل أنت متأكد من إلغاء هذه المعاملة؟')) {
            document.getElementById('cancel-form').submit();
        }
    }
</script>
@endpush
