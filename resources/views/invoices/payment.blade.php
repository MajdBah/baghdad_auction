@extends('layouts.app')

@section('title', 'تسجيل دفعة للفاتورة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تسجيل دفعة للفاتورة #{{ $invoice->invoice_number }}</h2>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-right"></i> العودة للفاتورة
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">تفاصيل الدفعة</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('invoices.make_payment', $invoice) }}" method="POST" id="paymentForm">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">المبلغ <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                        id="amount" name="amount" value="{{ old('amount', $remainingAmount) }}"
                                        min="0.01" max="{{ $remainingAmount }}" step="0.01" required>
                                    <span class="input-group-text">$</span>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">المبلغ المتبقي: {{ number_format($remainingAmount, 2) }} $</small>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_date" class="form-label">تاريخ الدفع <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('payment_date') is-invalid @enderror"
                                    id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                <select class="form-select @error('payment_method') is-invalid @enderror"
                                    id="payment_method" name="payment_method" required>
                                    <option value="" disabled selected>-- اختر طريقة الدفع --</option>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>نقداً</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                                    <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>بطاقة ائتمان</option>
                                    <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>شيك</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="reference_number" class="form-label">رقم المرجع</label>
                                <input type="text" class="form-control @error('reference_number') is-invalid @enderror"
                                    id="reference_number" name="reference_number" value="{{ old('reference_number') }}">
                                <small class="form-text text-muted">رقم الشيك، رقم العملية، إلخ...</small>
                                @error('reference_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="from_account_id" class="form-label">الحساب الدافع <span class="text-danger">*</span></label>
                                <select class="form-select @error('from_account_id') is-invalid @enderror"
                                    id="from_account_id" name="from_account_id" required>
                                    <option value="" disabled selected>-- اختر الحساب --</option>
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
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="create_receipt" name="create_receipt"
                                           {{ old('create_receipt', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="create_receipt">
                                        إنشاء إيصال دفع تلقائي
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                     id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-cash"></i> تسجيل الدفعة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- تفاصيل الفاتورة -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">تفاصيل الفاتورة</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
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
                                <td><strong>الحساب:</strong></td>
                                <td>{{ $invoice->account->name }}</td>
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
                        </table>
                    </div>

                    <!-- ملخص المدفوعات -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">ملخص المدفوعات</h6>
                        <div class="progress mb-3" style="height: 20px;">
                            @php
                                $paymentPercentage = $invoice->total_amount > 0 ? ($invoice->paid_amount / $invoice->total_amount) * 100 : 0;
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
                                <span class="text-danger fw-bold">{{ number_format($remainingAmount, 2) }} $</span>
                            </li>
                        </ul>
                    </div>

                    @if($invoice->car)
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">معلومات السيارة</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-1"><strong>الماركة/الموديل:</strong> {{ $invoice->car->make }} {{ $invoice->car->model }}</p>
                                    <p class="mb-1"><strong>سنة الصنع:</strong> {{ $invoice->car->year }}</p>
                                    <p class="mb-0"><strong>رقم الهيكل (VIN):</strong> {{ $invoice->car->vin }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-eye"></i> عرض تفاصيل الفاتورة الكاملة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const amountInput = document.getElementById('amount');
        const maxAmount = {{ $remainingAmount }};

        amountInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value > maxAmount) {
                this.value = maxAmount;
            }
        });

        // عند تغيير طريقة الدفع، نقوم بتحديث ما إذا كان حقل رقم المرجع مطلوباً
        const paymentMethodSelect = document.getElementById('payment_method');
        const referenceNumberInput = document.getElementById('reference_number');

        paymentMethodSelect.addEventListener('change', function() {
            const method = this.value;

            // إذا كانت طريقة الدفع تتطلب رقم مرجع
            if (method === 'bank_transfer' || method === 'check' || method === 'credit_card') {
                referenceNumberInput.setAttribute('required', 'required');
                referenceNumberInput.parentElement.querySelector('label').innerHTML = 'رقم المرجع <span class="text-danger">*</span>';
            } else {
                referenceNumberInput.removeAttribute('required');
                referenceNumberInput.parentElement.querySelector('label').innerHTML = 'رقم المرجع';
            }
        });

        // تنفيذ الفحص الأولي عند تحميل الصفحة
        if (paymentMethodSelect.value) {
            paymentMethodSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
