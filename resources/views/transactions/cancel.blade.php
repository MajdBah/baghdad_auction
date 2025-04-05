@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Cancel Transaction #{{ $transaction->id }}</div>

                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong>Warning!</strong> Are you sure you want to cancel this transaction? This action cannot be undone.
                    </div>

                    <div class="mb-4">
                        <h5>Transaction Details</h5>
                        <p><strong>ID:</strong> {{ $transaction->id }}</p>
                        <p><strong>Amount:</strong> {{ $transaction->amount }}</p>
                        <p><strong>Type:</strong> {{ $transaction->type }}</p>
                        <p><strong>Status:</strong> {{ $transaction->status }}</p>
                        <p><strong>Date:</strong> {{ $transaction->created_at->format('Y-m-d H:i') }}</p>
                    </div>

                    <form action="{{ route('transactions.do_cancel', $transaction) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-3">
                            <label for="cancel_reason">Reason for Cancellation (Optional)</label>
                            <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3"></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
