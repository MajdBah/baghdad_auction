@extends('layouts.app')

@section('title', 'تحويل رصيد بين الحسابات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تحويل رصيد بين الحسابات</h2>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-right"></i> العودة للمعاملات
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">تفاصيل التحويل</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('transactions.transfer') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="from_account_id" class="form-label">الحساب المصدر <span class="text-danger">*</span></label>
                                <select class="form-select @error('from_account_id') is-invalid @enderror"
                                       id="from_account_id" name="from_account_id" required>
                                    <option value="" selected disabled>-- اختر الحساب المصدر --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}"
                                            {{ old('from_account_id', request('from_account_id')) == $account->id ? 'selected' : '' }}
                                            data-balance="{{ $account->balance }}">
                                            {{ $account->name }} ({{ $account->account_number }}) - الرصيد: {{ number_format($account->balance, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="source-balance-warning" class="text-danger mt-1 d-none">
                                    <i class="bi bi-exclamation-triangle"></i> الرصيد الحالي غير كافٍ للتحويل
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="to_account_id" class="form-label">الحساب الوجهة <span class="text-danger">*</span></label>
                                <select class="form-select @error('to_account_id') is-invalid @enderror"
                                       id="to_account_id" name="to_account_id" required>
                                    <option value="" selected disabled>-- اختر الحساب الوجهة --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}"
                                            {{ old('to_account_id', request('to_account_id')) == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">مبلغ التحويل <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror"
                                           id="amount" name="amount" value="{{ old('amount') }}" required>
                                    <span class="input-group-text">$</span>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="form-label">تاريخ التحويل <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('transaction_date') is-invalid @enderror"
                                       id="transaction_date" name="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                                @error('transaction_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="with_commission" name="with_commission"
                                       {{ old('with_commission') ? 'checked' : '' }}>
                                <label class="form-check-label" for="with_commission">إضافة عمولة على التحويل</label>
                            </div>
                        </div>

                        <div class="mb-3 commission-field d-none">
                            <label for="commission_amount" class="form-label">مبلغ العمولة</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control @error('commission_amount') is-invalid @enderror"
                                       id="commission_amount" name="commission_amount" value="{{ old('commission_amount') }}">
                                <span class="input-group-text">$</span>
                            </div>
                            @error('commission_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">وصف التحويل</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                     id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> إجراء التحويل
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const withCommissionSwitch = document.getElementById('with_commission');
        const commissionField = document.querySelector('.commission-field');
        const fromAccountSelect = document.getElementById('from_account_id');
        const toAccountSelect = document.getElementById('to_account_id');
        const amountInput = document.getElementById('amount');
        const sourceBalanceWarning = document.getElementById('source-balance-warning');

        // Toggle commission field visibility
        withCommissionSwitch.addEventListener('change', function() {
            if (this.checked) {
                commissionField.classList.remove('d-none');
            } else {
                commissionField.classList.add('d-none');
            }
        });

        // Initialize commission field visibility
        if (withCommissionSwitch.checked) {
            commissionField.classList.remove('d-none');
        }

        // Prevent selecting the same account for source and destination
        toAccountSelect.addEventListener('change', function() {
            if (fromAccountSelect.value === this.value && this.value !== '') {
                alert('لا يمكن اختيار نفس الحساب للمصدر والوجهة');
                this.value = '';
            }
        });

        fromAccountSelect.addEventListener('change', function() {
            if (toAccountSelect.value === this.value && this.value !== '') {
                alert('لا يمكن اختيار نفس الحساب للمصدر والوجهة');
                toAccountSelect.value = '';
            }
            validateAmount();
        });

        // Validate amount against source account balance
        function validateAmount() {
            if (fromAccountSelect.value) {
                const selectedOption = fromAccountSelect.options[fromAccountSelect.selectedIndex];
                const sourceBalance = parseFloat(selectedOption.dataset.balance);
                const amount = parseFloat(amountInput.value) || 0;
                const commissionAmount = parseFloat(document.getElementById('commission_amount').value) || 0;
                const totalAmount = withCommissionSwitch.checked ? amount + commissionAmount : amount;

                if (totalAmount > sourceBalance) {
                    sourceBalanceWarning.classList.remove('d-none');
                } else {
                    sourceBalanceWarning.classList.add('d-none');
                }
            }
        }

        amountInput.addEventListener('input', validateAmount);
        document.getElementById('commission_amount').addEventListener('input', validateAmount);
        withCommissionSwitch.addEventListener('change', validateAmount);

        // Initial validation
        if (fromAccountSelect.value) {
            validateAmount();
        }
    });
</script>
@endpush
