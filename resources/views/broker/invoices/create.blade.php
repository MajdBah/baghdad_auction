@extends('layouts.app')

@section('content')
<div class="container" dir="rtl" lang="ar">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle ms-2"></i>إنشاء فاتورة جديدة للوسيط</h5>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('broker.invoices.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="invoice_number" class="form-label">رقم الفاتورة <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('invoice_number') is-invalid @enderror"
                                        id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}" required>
                                    @error('invoice_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="issue_date" class="form-label">تاريخ الإصدار <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                        id="issue_date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="due_date" class="form-label">تاريخ الاستحقاق</label>
                                    <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                        id="due_date" name="due_date" value="{{ old('due_date') }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="broker_account_id" class="form-label">حساب الوسيط <span class="text-danger">*</span></label>
                                    <select class="form-select @error('broker_account_id') is-invalid @enderror"
                                        id="broker_account_id" name="broker_account_id" required>
                                        <option value="">-- اختر حساب الوسيط --</option>
                                        @foreach($brokerAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('broker_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->name }} ({{ $account->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('broker_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="other_account_id" class="form-label">الحساب الآخر <span class="text-danger">*</span></label>
                                    <select class="form-select @error('other_account_id') is-invalid @enderror"
                                        id="other_account_id" name="other_account_id" required>
                                        <option value="">-- اختر الحساب الآخر --</option>
                                        <optgroup label="حسابات العملاء">
                                            @foreach($clientAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('other_account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} ({{ $account->account_number }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="حسابات شركات الشحن">
                                            @foreach($shippingAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('other_account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} ({{ $account->account_number }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                    @error('other_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">اتجاه الفاتورة <span class="text-danger">*</span></label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="direction" id="direction_positive"
                                                value="positive" {{ old('direction', 'positive') == 'positive' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="direction_positive">
                                                موجبة للوسيط (من العميل إلى الوسيط)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="direction" id="direction_negative"
                                                value="negative" {{ old('direction') == 'negative' ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="direction_negative">
                                                سالبة للوسيط (من الوسيط إلى الشحن)
                                            </label>
                                        </div>
                                    </div>
                                    @error('direction')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="subtotal" class="form-label">المبلغ الفرعي <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('subtotal') is-invalid @enderror"
                                        id="subtotal" name="subtotal" value="{{ old('subtotal', 0) }}" required onchange="calculateTotal()">
                                    @error('subtotal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tax_rate" class="form-label">نسبة الضريبة (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('tax_rate') is-invalid @enderror"
                                        id="tax_rate" name="tax_rate" value="{{ old('tax_rate', 0) }}" onchange="calculateTotal()">
                                    @error('tax_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="shipping_fee" class="form-label">رسوم الشحن</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('shipping_fee') is-invalid @enderror"
                                        id="shipping_fee" name="shipping_fee" value="{{ old('shipping_fee', 0) }}" onchange="calculateTotal()">
                                    @error('shipping_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="discount" class="form-label">الخصم</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('discount') is-invalid @enderror"
                                        id="discount" name="discount" value="{{ old('discount', 0) }}" onchange="calculateTotal()">
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">الضريبة المحسوبة:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="calculated_tax" readonly>
                                        <span class="input-group-text">د.ع</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">المبلغ الإجمالي:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="calculated_total" readonly>
                                        <span class="input-group-text">د.ع</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="alert alert-info mb-4">
                            <h6 class="mb-0"><i class="fas fa-info-circle ms-2"></i>معلومات حول اتجاه الفاتورة</h6>
                            <ul class="mt-2 mb-0">
                                <li>
                                    <strong>فاتورة موجبة للوسيط:</strong> من حساب العميل إلى حساب الوسيط (إيرادات للوسيط)
                                </li>
                                <li>
                                    <strong>فاتورة سالبة للوسيط:</strong> من حساب الوسيط إلى حساب شركة الشحن (مصروفات للوسيط)
                                </li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('broker.invoices.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-right ms-1"></i>العودة للقائمة
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save ms-1"></i>حفظ الفاتورة
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
    // حساب المبلغ الإجمالي للفاتورة
    function calculateTotal() {
        const subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
        const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
        const shippingFee = parseFloat(document.getElementById('shipping_fee').value) || 0;
        const discount = parseFloat(document.getElementById('discount').value) || 0;

        const taxAmount = subtotal * (taxRate / 100);
        const totalAmount = subtotal + taxAmount + shippingFee - discount;

        document.getElementById('calculated_tax').value = taxAmount.toFixed(2);
        document.getElementById('calculated_total').value = totalAmount.toFixed(2);
    }

    // تحديث الحسابات عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
    });
</script>
@endpush
