@extends('layouts.app')

@section('title', 'Create Account Transfer')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">تحويل بين الحسابات</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">المعاملات المالية</a></li>
        <li class="breadcrumb-item active">تحويل جديد</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-exchange-alt me-1"></i>
            تحويل مالي بين الحسابات
        </div>
        <div class="card-body">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('transfers.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="from_account_id" name="from_account_id" required>
                                <option value="">اختر حساب المصدر</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} ({{ $account->account_number }}) - الرصيد: {{ number_format($account->balance, 2) }}
                                </option>
                                @endforeach
                            </select>
                            <label for="from_account_id">حساب المصدر</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="to_account_id" name="to_account_id" required>
                                <option value="">اختر حساب الوجهة</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} ({{ $account->account_number }})
                                </option>
                                @endforeach
                            </select>
                            <label for="to_account_id">حساب الوجهة</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="0.00" required />
                            <label for="amount">المبلغ</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="transaction_date" name="transaction_date" type="date" value="{{ old('transaction_date', date('Y-m-d')) }}" required />
                            <label for="transaction_date">تاريخ التحويل</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="reference_number" name="reference_number" type="text" value="{{ old('reference_number') }}" placeholder="رقم المرجع" />
                            <label for="reference_number">رقم المرجع (اختياري)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-3 mt-4">
                            <input class="form-check-input" type="checkbox" id="with_commission" name="with_commission" {{ old('with_commission') ? 'checked' : '' }} onchange="toggleCommission(this)">
                            <label class="form-check-label" for="with_commission">تطبيق عمولة؟</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3" id="commission_section" style="{{ old('with_commission') ? '' : 'display:none' }}">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="commission_percentage" name="commission_percentage" type="number" min="0" max="100" step="0.01" value="{{ old('commission_percentage', 5) }}" placeholder="0" />
                            <label for="commission_percentage">نسبة العمولة (%)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="commission_amount" type="text" placeholder="0" disabled />
                            <label for="commission_amount">مبلغ العمولة (محسوب)</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="description" name="description" style="height: 100px" required>{{ old('description') }}</textarea>
                    <label for="description">وصف التحويل</label>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">إجراء التحويل</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleCommission(checkbox) {
        const commissionSection = document.getElementById('commission_section');
        if (checkbox.checked) {
            commissionSection.style.display = 'flex';
        } else {
            commissionSection.style.display = 'none';
        }
        updateCommissionAmount();
    }

    function updateCommissionAmount() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const withCommission = document.getElementById('with_commission').checked;
        const commissionPercentage = parseFloat(document.getElementById('commission_percentage').value) || 0;

        if (withCommission && amount > 0) {
            const commissionAmount = amount * (commissionPercentage / 100);
            document.getElementById('commission_amount').value = commissionAmount.toFixed(2);
        } else {
            document.getElementById('commission_amount').value = '0.00';
        }
    }

    // Add event listeners
    document.getElementById('amount').addEventListener('input', updateCommissionAmount);
    document.getElementById('commission_percentage').addEventListener('input', updateCommissionAmount);

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCommissionAmount();
    });
</script>
@endsection
