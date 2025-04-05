@extends('layouts.app')

@section('title', 'تنفيذ العملية المالية')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تنفيذ العملية المالية</h2>
        <div>
            <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> العودة للعملية
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">تأكيد تنفيذ العملية المالية</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>تحذير:</strong> تنفيذ هذه العملية سيؤدي إلى تحديث أرصدة الحسابات المعنية.
                    </div>

                    <div class="row mb-4">
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
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%;">المبلغ:</th>
                                    <td><strong>{{ number_format($transaction->amount, 2) }} $</strong></td>
                                </tr>
                                <tr>
                                    <th>من حساب:</th>
                                    <td>{{ $transaction->fromAccount->name ?? 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <th>إلى حساب:</th>
                                    <td>{{ $transaction->toAccount->name ?? 'غير محدد' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <form action="{{ route('transactions.process', $transaction) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-3">
                            <label for="notes">ملاحظات إضافية (اختياري)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> تنفيذ العملية المالية
                            </button>
                            <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">التأثير المالي</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> تأثير تنفيذ هذه العملية:</h6>
                    </div>

                    @if($transaction->fromAccount)
                    <div class="mb-3">
                        <h6>رصيد {{ $transaction->fromAccount->name }} الحالي:</h6>
                        <p class="h4">{{ number_format($transaction->fromAccount->balance, 2) }} $</p>
                        <p class="text-danger">
                            <i class="bi bi-arrow-down"></i>
                            بعد التنفيذ: <strong>{{ number_format($transaction->fromAccount->balance - $transaction->amount, 2) }} $</strong>
                        </p>
                    </div>
                    @endif

                    @if($transaction->toAccount)
                    <div>
                        <h6>رصيد {{ $transaction->toAccount->name }} الحالي:</h6>
                        <p class="h4">{{ number_format($transaction->toAccount->balance, 2) }} $</p>
                        <p class="text-success">
                            <i class="bi bi-arrow-up"></i>
                            بعد التنفيذ: <strong>{{ number_format($transaction->toAccount->balance + $transaction->amount, 2) }} $</strong>
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
