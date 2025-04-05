@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('Auto Payment Distribution') }}</div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('payments.auto.process') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="account_id" class="col-md-4 col-form-label text-md-end">{{ __('Select Account') }}</label>
                            <div class="col-md-6">
                                <select id="account_id" class="form-select @error('account_id') is-invalid @enderror" name="account_id" required>
                                    <option value="">-- Select Account --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('account_id', $selectedAccountId) == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('account_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="amount" class="col-md-4 col-form-label text-md-end">{{ __('Payment Amount') }}</label>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input id="amount" type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" required step="0.01" min="0.01">
                                </div>
                                @error('amount')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="payment_method" class="col-md-4 col-form-label text-md-end">{{ __('Payment Method') }}</label>
                            <div class="col-md-6">
                                <select id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" required>
                                    <option value="">-- Select Payment Method --</option>
                                    <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                    <option value="debit_card" {{ old('payment_method') == 'debit_card' ? 'selected' : '' }}>Debit Card</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                </select>
                                @error('payment_method')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="reference_number" class="col-md-4 col-form-label text-md-end">{{ __('Reference Number') }}</label>
                            <div class="col-md-6">
                                <input id="reference_number" type="text" class="form-control @error('reference_number') is-invalid @enderror" name="reference_number" value="{{ old('reference_number') }}">
                                @error('reference_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="notes" class="col-md-4 col-form-label text-md-end">{{ __('Notes') }}</label>
                            <div class="col-md-6">
                                <textarea id="notes" class="form-control @error('notes') is-invalid @enderror" name="notes">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Process Auto Payment') }}
                                </button>
                                <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4" id="invoices-container" style="display: none;">
                <div class="card-header">{{ __('Unpaid Invoices') }}</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody id="unpaid-invoices">
                                <!-- AJAX will load unpaid invoices here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Load unpaid invoices when account is selected
        $('#account_id').change(function() {
            var accountId = $(this).val();
            if (accountId) {
                loadUnpaidInvoices(accountId);
            } else {
                $('#invoices-container').hide();
            }
        });

        // If account is already selected on page load, load its invoices
        var initialAccountId = $('#account_id').val();
        if (initialAccountId) {
            loadUnpaidInvoices(initialAccountId);
        }

        function loadUnpaidInvoices(accountId) {
            $.ajax({
                url: "{{ route('payments.auto.get-invoices', '') }}/" + accountId,
                type: 'GET',
                success: function(data) {
                    var tbody = $('#unpaid-invoices');
                    tbody.empty();

                    if (data.length > 0) {
                        $.each(data, function(index, invoice) {
                            var row = '<tr>' +
                                '<td>' + invoice.invoice_number + '</td>' +
                                '<td>' + invoice.invoice_date + '</td>' +
                                '<td>' + invoice.due_date + '</td>' +
                                '<td>$' + parseFloat(invoice.total_amount).toFixed(2) + '</td>' +
                                '<td>$' + parseFloat(invoice.amount_paid).toFixed(2) + '</td>' +
                                '<td>$' + parseFloat(invoice.balance).toFixed(2) + '</td>' +
                                '</tr>';
                            tbody.append(row);
                        });
                        $('#invoices-container').show();
                    } else {
                        tbody.append('<tr><td colspan="6" class="text-center">No unpaid invoices found</td></tr>');
                        $('#invoices-container').show();
                    }
                },
                error: function() {
                    $('#unpaid-invoices').html('<tr><td colspan="6" class="text-center text-danger">Error loading invoices</td></tr>');
                    $('#invoices-container').show();
                }
            });
        }
    });
</script>
@endpush
