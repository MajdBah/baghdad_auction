@extends('layouts.app')

@section('title', 'إنشاء عملية مالية جديدة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إنشاء عملية مالية جديدة</h2>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-right"></i> العودة للعمليات المالية
        </a>
    </div>

    <form action="{{ route('transactions.store') }}" method="POST" id="transactionForm">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <!-- البيانات الأساسية للعملية -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">البيانات الأساسية</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">نوع العملية <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror"
                                        id="type" name="type" required>
                                    <option value="" selected disabled>-- اختر نوع العملية --</option>
                                    @foreach($transactionTypes as $value => $label)
                                        <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="form-label">تاريخ العملية <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('transaction_date') is-invalid @enderror"
                                       id="transaction_date" name="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                                @error('transaction_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="from_account_id" class="form-label">من حساب</label>
                                <select class="form-select @error('from_account_id') is-invalid @enderror"
                                        id="from_account_id" name="from_account_id">
                                    <option value="">-- اختر الحساب المصدر (اختياري) --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="to_account_id" class="form-label">إلى حساب</label>
                                <select class="form-select @error('to_account_id') is-invalid @enderror"
                                        id="to_account_id" name="to_account_id">
                                    <option value="">-- اختر الحساب الهدف (اختياري) --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
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
                                <label for="car_id" class="form-label">السيارة المرتبطة</label>
                                <select class="form-select @error('car_id') is-invalid @enderror"
                                        id="car_id" name="car_id">
                                    <option value="">-- اختر السيارة (اختياري) --</option>
                                    @foreach($cars as $car)
                                        <option value="{{ $car->id }}" {{ old('car_id') == $car->id ? 'selected' : '' }}>
                                            {{ $car->make }} {{ $car->model }} ({{ $car->year }}) - {{ $car->vin }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('car_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="reference_number" class="form-label">الرقم المرجعي</label>
                                <input type="text" class="form-control @error('reference_number') is-invalid @enderror"
                                       id="reference_number" name="reference_number" value="{{ old('reference_number') }}">
                                <small class="form-text text-muted">رقم مرجعي خارجي (اختياري)</small>
                                @error('reference_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">المبلغ <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror"
                                           id="amount" name="amount" value="{{ old('amount', 0) }}" required>
                                    <span class="input-group-text">$</span>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">حالة العملية <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                        id="status" name="status" required>
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>معلقة</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>مكتملة</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>ملغية</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1"
                                       id="with_commission" name="with_commission"
                                       {{ old('with_commission') ? 'checked' : '' }}>
                                <label class="form-check-label" for="with_commission">
                                    مع عمولة
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="commission_container" style="{{ old('with_commission') ? 'display: block;' : 'display: none;' }}">
                            <label for="commission_amount" class="form-label">مبلغ العمولة</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control @error('commission_amount') is-invalid @enderror"
                                       id="commission_amount" name="commission_amount" value="{{ old('commission_amount', 0) }}">
                                <span class="input-group-text">$</span>
                            </div>
                            @error('commission_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                     id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- ملخص المعاملة -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">ملخص المعاملة</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4" id="transaction_summary">
                            <p>يرجى ملء بيانات المعاملة لعرض الملخص.</p>
                        </div>

                        <div class="alert alert-warning mb-4" id="warning_pending" style="display: none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>تنبيه:</strong> هذه العملية ستكون بحالة معلقة ولن تؤثر على أرصدة الحسابات حتى يتم تأكيدها.
                        </div>

                        <div class="alert alert-warning mb-4" id="warning_same_account" style="display: none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>تنبيه:</strong> لا يمكن اختيار نفس الحساب كمصدر ووجهة للتحويل.
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> حفظ المعاملة
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const fromAccountSelect = document.getElementById('from_account_id');
        const toAccountSelect = document.getElementById('to_account_id');
        const carSelect = document.getElementById('car_id');
        const amountInput = document.getElementById('amount');
        const withCommissionCheckbox = document.getElementById('with_commission');
        const commissionContainer = document.getElementById('commission_container');
        const commissionAmountInput = document.getElementById('commission_amount');
        const statusSelect = document.getElementById('status');
        const transactionSummary = document.getElementById('transaction_summary');
        const warningSameAccount = document.getElementById('warning_same_account');
        const warningPending = document.getElementById('warning_pending');

        // Función para actualizar el resumen de la transacción
        function updateTransactionSummary() {
            let type = typeSelect.options[typeSelect.selectedIndex]?.text || '';
            let fromAccount = fromAccountSelect.options[fromAccountSelect.selectedIndex]?.text || 'غير محدد';
            let toAccount = toAccountSelect.options[toAccountSelect.selectedIndex]?.text || 'غير محدد';
            let amount = parseFloat(amountInput.value) || 0;
            let withCommission = withCommissionCheckbox.checked;
            let commissionAmount = parseFloat(commissionAmountInput.value) || 0;
            let total = withCommission ? amount + commissionAmount : amount;
            let status = statusSelect.options[statusSelect.selectedIndex]?.text || '';

            // Verificar si los mismos
            if (fromAccountSelect.value && toAccountSelect.value && fromAccountSelect.value === toAccountSelect.value) {
                warningSameAccount.style.display = 'block';
            } else {
                warningSameAccount.style.display = 'none';
            }

            // Mostrar warning de pending
            if (statusSelect.value === 'pending') {
                warningPending.style.display = 'block';
            } else {
                warningPending.style.display = 'none';
            }

            let summaryHtml = `
                <h6>نوع المعاملة: <strong>${type}</strong></h6>
                <hr>
                <p>من: <strong>${fromAccount}</strong></p>
                <p>إلى: <strong>${toAccount}</strong></p>
                <p>المبلغ: <strong>${amount.toFixed(2)} $</strong></p>
                ${withCommission ? `<p>العمولة: <strong>${commissionAmount.toFixed(2)} $</strong></p>` : ''}
                <hr>
                <p class="fw-bold">الإجمالي: <strong>${total.toFixed(2)} $</strong></p>
                <p>الحالة: <strong>${status}</strong></p>
            `;

            transactionSummary.innerHTML = summaryHtml;
        }

        // Inicializar y añadir eventos
        typeSelect.addEventListener('change', updateTypeFields);
        fromAccountSelect.addEventListener('change', updateTransactionSummary);
        toAccountSelect.addEventListener('change', updateTransactionSummary);
        amountInput.addEventListener('input', updateTransactionSummary);
        withCommissionCheckbox.addEventListener('change', function() {
            commissionContainer.style.display = this.checked ? 'block' : 'none';
            updateTransactionSummary();
        });
        commissionAmountInput.addEventListener('input', updateTransactionSummary);
        statusSelect.addEventListener('change', updateTransactionSummary);

        // Función para actualizar campos según el tipo de transacción
        function updateTypeFields() {
            const type = typeSelect.value;

            switch(type) {
                case 'purchase':
                    fromAccountSelect.value = ''; // From intermediary by default
                    fromAccountSelect.required = false;
                    toAccountSelect.required = true;
                    carSelect.required = true;
                    break;
                case 'shipping':
                    fromAccountSelect.required = true;
                    toAccountSelect.required = false;
                    carSelect.required = true;
                    break;
                case 'transfer':
                    fromAccountSelect.required = true;
                    toAccountSelect.required = true;
                    carSelect.required = false;
                    break;
                case 'payment':
                    fromAccountSelect.required = true;
                    toAccountSelect.required = true;
                    carSelect.required = false;
                    break;
                case 'commission':
                    fromAccountSelect.required = true;
                    toAccountSelect.required = false;
                    carSelect.required = false;
                    withCommissionCheckbox.checked = true;
                    commissionContainer.style.display = 'block';
                    break;
                case 'refund':
                    fromAccountSelect.required = false;
                    toAccountSelect.required = true;
                    carSelect.required = false;
                    break;
                default:
                    fromAccountSelect.required = false;
                    toAccountSelect.required = false;
                    carSelect.required = false;
            }

            updateTransactionSummary();
        }

        // Inicializar campos
        updateTypeFields();
        updateTransactionSummary();
    });
</script>
@endpush
