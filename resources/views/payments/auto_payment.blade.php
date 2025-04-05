@extends('layouts.app')

@section('content')
<div class="container" dir="rtl" lang="ar">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-money-bill-wave ms-2"></i>توزيع دفعة تلقائي</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('payments.auto.form') }}" class="mb-4">
                <div class="form-group">
                    <label for="account_id" class="fw-bold">اختر الحساب:</label>
                    <select name="account_id" id="account_id" class="form-control form-select" onchange="this.form.submit()">
                        <option value="">-- اختر الحساب --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ $selectedAccount && $selectedAccount->id == $account->id ? 'selected' : '' }}>
                                {{ $account->name }} - {{ $account->account_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if($selectedAccount)
                <hr>
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="fas fa-user ms-2"></i>الحساب: {{ $selectedAccount->name }}</h5>
                        <span class="badge bg-secondary">رقم الحساب: {{ $selectedAccount->account_number }}</span>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>الرصيد الحالي:</strong> {{ number_format($selectedAccount->balance, 2) }} د.ع
                        </div>
                        <div class="col-md-4">
                            <strong>عدد الفواتير غير المدفوعة:</strong> {{ $unpaidInvoices->count() }}
                        </div>
                        <div class="col-md-4">
                            <strong>إجمالي المستحقات:</strong> {{ number_format($unpaidInvoices->sum('balance'), 2) }} د.ع
                        </div>
                    </div>
                </div>

                @if($unpaidInvoices->count() > 0)
                    <form method="POST" action="{{ route('payments.auto.process') }}" id="paymentForm">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount" class="fw-bold">مبلغ الدفعة:</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" required value="{{ old('amount') }}" oninput="updateInvoiceSelections()">
                                        <div class="input-group-append">
                                            <span class="input-group-text">د.ع</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_date" class="fw-bold">تاريخ الدفعة:</label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_method" class="fw-bold">طريقة الدفع:</label>
                                    <select name="payment_method" id="payment_method" class="form-control form-select">
                                        <option value="cash">نقدي</option>
                                        <option value="bank_transfer">تحويل بنكي</option>
                                        <option value="check">شيك</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reference_number" class="fw-bold">رقم المرجع (اختياري):</label>
                                    <input type="text" name="reference_number" id="reference_number" class="form-control" value="{{ old('reference_number') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="fw-bold">وصف الدفعة (اختياري):</label>
                            <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', 'دفعة تلقائية') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="distribution_method" id="auto_distribution" value="auto" checked onchange="toggleDistributionMethod()">
                                <label class="form-check-label fw-bold" for="auto_distribution">
                                    توزيع تلقائي من الأقدم للأحدث
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="distribution_method" id="manual_distribution" value="manual" onchange="toggleDistributionMethod()">
                                <label class="form-check-label fw-bold" for="manual_distribution">
                                    اختيار الفواتير يدوياً
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info" id="auto_distribution_info">
                            <h6 class="fw-bold"><i class="fas fa-info-circle ms-2"></i>الفواتير غير المدفوعة:</h6>
                            <p>سيتم توزيع الدفعة تلقائياً على الفواتير غير المدفوعة بدءاً من الأقدم.</p>
                        </div>

                        <div id="manual_distribution_section" style="display: none;">
                            <div class="alert alert-warning">
                                <h6 class="fw-bold"><i class="fas fa-hand-point-right ms-2"></i>اختر الفواتير يدوياً:</h6>
                                <p>يمكنك تحديد الفواتير التي تريد دفعها والمبلغ المخصص لكل فاتورة.</p>
                            </div>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">تحديد الكل</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="unselectAll">إلغاء تحديد الكل</button>
                                <button type="button" class="btn btn-sm btn-outline-success" id="distributeRemaining">توزيع المبلغ المتبقي</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="invoicesTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">
                                            <input type="checkbox" id="checkAll" style="display: none;">
                                        </th>
                                        <th>رقم الفاتورة</th>
                                        <th>تاريخ الإصدار</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>المبلغ الكلي</th>
                                        <th>المبلغ المدفوع</th>
                                        <th>الرصيد المتبقي</th>
                                        <th style="width: 180px;">مبلغ الدفع</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidInvoices as $index => $invoice)
                                        <tr class="{{ $invoice->isOverdue() ? 'table-danger' : '' }}">
                                            <td class="text-center">
                                                <input type="checkbox" name="selected_invoices[]" value="{{ $invoice->id }}" class="invoice-checkbox" data-balance="{{ $invoice->balance }}">
                                            </td>
                                            <td>{{ $invoice->invoice_number }}</td>
                                            <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                                            <td>{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}</td>
                                            <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                                            <td>{{ number_format($invoice->balance, 2) }}</td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="{{ $invoice->balance }}"
                                                    name="payment_amounts[{{ $invoice->id }}]"
                                                    class="form-control form-control-sm payment-amount"
                                                    data-invoice-id="{{ $invoice->id }}"
                                                    data-max-amount="{{ $invoice->balance }}"
                                                    disabled>
                                            </td>
                                            <td>
                                                <span class="badge {{ $invoice->isOverdue() ? 'bg-danger' : ($invoice->isPartiallyPaid() ? 'bg-warning' : 'bg-secondary') }}">
                                                    {{ $invoice->isOverdue() ? 'متأخرة' : ($invoice->isPartiallyPaid() ? 'مدفوعة جزئياً' : 'غير مدفوعة') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-primary">
                                    <tr>
                                        <th colspan="6" class="text-end">الإجمالي</th>
                                        <th>{{ number_format($unpaidInvoices->sum('balance'), 2) }}</th>
                                        <th id="totalAllocated">0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <span class="badge bg-info p-2 mb-2" id="remainingAmount">المبلغ المتبقي غير الموزع: 0.00 د.ع</span>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-money-bill-wave ms-2"></i>توزيع الدفعة</button>
                                <a href="{{ route('accounts.show', $selectedAccount->id) }}" class="btn btn-secondary"><i class="fas fa-times ms-2"></i>إلغاء</a>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning">
                        <p class="mb-0"><i class="fas fa-exclamation-triangle ms-2"></i>لا توجد فواتير غير مدفوعة لهذا الحساب.</p>
                        <a href="{{ route('accounts.show', $selectedAccount->id) }}" class="btn btn-secondary mt-2"><i class="fas fa-user ms-2"></i>العودة إلى الحساب</a>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        toggleDistributionMethod();

        $('#selectAll').click(function() {
            $('.invoice-checkbox').prop('checked', true).trigger('change');
        });

        $('#unselectAll').click(function() {
            $('.invoice-checkbox').prop('checked', false).trigger('change');
        });

        $('#checkAll').change(function() {
            $('.invoice-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });

        $('.invoice-checkbox').change(function() {
            let $paymentInput = $(this).closest('tr').find('.payment-amount');

            if ($(this).is(':checked')) {
                $paymentInput.prop('disabled', false);
            } else {
                $paymentInput.prop('disabled', true);
                $paymentInput.val('');
            }

            updateTotals();
        });

        $('.payment-amount').on('input', function() {
            let maxAmount = parseFloat($(this).data('max-amount'));
            let value = parseFloat($(this).val()) || 0;

            if (value > maxAmount) {
                $(this).val(maxAmount);
            }

            updateTotals();
        });

        $('#distributeRemaining').click(function() {
            distributeRemainingAmount();
        });

        $('#amount').on('input', function() {
            updateTotals();
        });
    });

    function toggleDistributionMethod() {
        let method = $('input[name="distribution_method"]:checked').val();

        if (method === 'auto') {
            $('#auto_distribution_info').show();
            $('#manual_distribution_section').hide();
            $('.invoice-checkbox').prop('checked', false).trigger('change');
            $('#checkAll').hide();
            $('.invoice-checkbox').closest('td').hide();
            $('.payment-amount').prop('disabled', true);
            $('.payment-amount').val('');
        } else {
            $('#auto_distribution_info').hide();
            $('#manual_distribution_section').show();
            $('#checkAll').show();
            $('.invoice-checkbox').closest('td').show();
        }

        updateTotals();
    }

    function updateTotals() {
        let totalPaymentAmount = parseFloat($('#amount').val()) || 0;
        let totalAllocated = 0;

        $('.payment-amount').each(function() {
            if (!$(this).prop('disabled')) {
                totalAllocated += parseFloat($(this).val()) || 0;
            }
        });

        $('#totalAllocated').text(totalAllocated.toFixed(2));

        let remaining = totalPaymentAmount - totalAllocated;
        $('#remainingAmount').text('المبلغ المتبقي غير الموزع: ' + remaining.toFixed(2) + ' د.ع');

        if (remaining < 0) {
            $('#remainingAmount').removeClass('bg-info').addClass('bg-danger');
        } else {
            $('#remainingAmount').removeClass('bg-danger').addClass('bg-info');
        }
    }

    function distributeRemainingAmount() {
        let totalPaymentAmount = parseFloat($('#amount').val()) || 0;
        let totalAllocated = 0;
        let checkedInvoices = [];

        // Calculate current allocated amount and collect checked invoices
        $('.invoice-checkbox:checked').each(function() {
            let $paymentInput = $(this).closest('tr').find('.payment-amount');
            let currentValue = parseFloat($paymentInput.val()) || 0;
            totalAllocated += currentValue;

            checkedInvoices.push({
                id: $paymentInput.data('invoice-id'),
                element: $paymentInput,
                maxAmount: parseFloat($paymentInput.data('max-amount')),
                currentValue: currentValue
            });
        });

        let remaining = totalPaymentAmount - totalAllocated;

        if (remaining <= 0 || checkedInvoices.length === 0) {
            return;
        }

        // Sort invoices by oldest first (we're assuming the DOM order is by date)
        checkedInvoices.sort((a, b) => {
            return a.element.closest('tr').index() - b.element.closest('tr').index();
        });

        // Distribute remaining amount starting from oldest invoice
        for (let i = 0; i < checkedInvoices.length; i++) {
            if (remaining <= 0) break;

            let invoice = checkedInvoices[i];
            let currentValue = invoice.currentValue;
            let maxValue = invoice.maxAmount;
            let availableSpace = maxValue - currentValue;

            if (availableSpace > 0) {
                let amountToAdd = Math.min(availableSpace, remaining);
                let newValue = currentValue + amountToAdd;

                invoice.element.val(newValue.toFixed(2));
                remaining -= amountToAdd;
            }
        }

        updateTotals();
    }

    function updateInvoiceSelections() {
        if ($('input[name="distribution_method"]:checked').val() === 'manual') {
            distributeRemainingAmount();
        }
    }
</script>
@endpush
