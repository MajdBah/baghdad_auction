<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'all');
        $query = Transaction::with(['fromAccount', 'toAccount', 'car']);

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $transactions = $query->latest()->paginate(15);

        return view('transactions.index', compact('transactions', 'type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $cars = Car::orderBy('created_at', 'desc')->get();
        $transactionTypes = [
            'purchase' => 'Car Purchase',
            'shipping' => 'Shipping Cost',
            'transfer' => 'Account Transfer',
            'payment' => 'Payment',
            'commission' => 'Commission',
            'refund' => 'Refund'
        ];

        return view('transactions.create', compact('accounts', 'cars', 'transactionTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'from_account_id' => 'nullable|exists:accounts,id',
            'to_account_id' => 'nullable|exists:accounts,id',
            'car_id' => 'nullable|exists:cars,id',
            'amount' => 'required|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'with_commission' => 'nullable|boolean',
            'reference_number' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        if ($request->from_account_id == $request->to_account_id && $request->from_account_id !== null) {
            return back()->withErrors(['from_account_id' => 'Source and destination accounts cannot be the same'])->withInput();
        }

        // Generate a unique transaction number
        $transactionNumber = 'TXN-' . strtoupper(Str::random(8));

        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'transaction_number' => $transactionNumber,
                'type' => $request->type,
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'car_id' => $request->car_id,
                'amount' => $request->amount,
                'commission_amount' => $request->commission_amount ?? 0,
                'with_commission' => $request->with_commission ?? false,
                'reference_number' => $request->reference_number,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            // Update account balances if transaction is completed
            if ($request->status === 'completed') {
                $this->updateAccountBalances($transaction);
            }

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to create transaction: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        return view('transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        // Only pending transactions can be edited
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Only pending transactions can be edited');
        }

        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $cars = Car::orderBy('created_at', 'desc')->get();
        $transactionTypes = [
            'purchase' => 'Car Purchase',
            'shipping' => 'Shipping Cost',
            'transfer' => 'Account Transfer',
            'payment' => 'Payment',
            'commission' => 'Commission',
            'refund' => 'Refund'
        ];

        return view('transactions.edit', compact('transaction', 'accounts', 'cars', 'transactionTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        // Only pending transactions can be updated
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Only pending transactions can be updated');
        }

        $request->validate([
            'type' => 'required|string',
            'from_account_id' => 'nullable|exists:accounts,id',
            'to_account_id' => 'nullable|exists:accounts,id',
            'car_id' => 'nullable|exists:cars,id',
            'amount' => 'required|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'with_commission' => 'nullable|boolean',
            'reference_number' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        if ($request->from_account_id == $request->to_account_id && $request->from_account_id !== null) {
            return back()->withErrors(['from_account_id' => 'Source and destination accounts cannot be the same'])->withInput();
        }

        DB::beginTransaction();

        try {
            $oldStatus = $transaction->status;

            $transaction->update([
                'type' => $request->type,
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'car_id' => $request->car_id,
                'amount' => $request->amount,
                'commission_amount' => $request->commission_amount ?? 0,
                'with_commission' => $request->with_commission ?? false,
                'reference_number' => $request->reference_number,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            // Update account balances if transaction status changed to completed
            if ($oldStatus !== 'completed' && $request->status === 'completed') {
                $this->updateAccountBalances($transaction);
            }

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to update transaction: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        // Only pending transactions can be deleted
        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Only pending transactions can be deleted');
        }

        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully');
    }

    /**
     * Show the form for processing a transaction
     */
    public function showProcessForm(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Only pending transactions can be processed');
        }

        return view('transactions.process', compact('transaction'));
    }

    /**
     * Process a transaction (change status to completed)
     */
    public function process(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Transaction is already processed or cancelled');
        }

        DB::beginTransaction();

        try {
            $transaction->update(['status' => 'completed']);

            // Update account balances
            $this->updateAccountBalances($transaction);

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transaction processed successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to process transaction: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for cancelling a transaction
     */
    public function showCancelForm(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Only pending transactions can be cancelled');
        }

        return view('transactions.cancel', compact('transaction'));
    }

    /**
     * Cancel a transaction
     */
    public function cancel(Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Only pending transactions can be cancelled');
        }

        $transaction->update(['status' => 'cancelled']);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaction cancelled successfully');
    }

    /**
     * Show form for account transfers
     */
    public function transferForm()
    {
        $accounts = Account::where('is_active', true)->orderBy('name')->get();

        return view('transactions.transfer', compact('accounts'));
    }

    /**
     * Process account transfer
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'with_commission' => 'nullable|boolean',
            'commission_amount' => 'nullable|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        // Generate a unique transaction number
        $transactionNumber = 'TRF-' . strtoupper(Str::random(8));

        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'transfer',
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'amount' => $request->amount,
                'commission_amount' => $request->commission_amount ?? 0,
                'with_commission' => $request->with_commission ?? false,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);

            // Update account balances
            $this->updateAccountBalances($transaction);

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transfer completed successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to complete transfer: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update account balances for a transaction
     */
    private function updateAccountBalances(Transaction $transaction)
    {
        if ($transaction->status !== 'completed') {
            return;
        }

        // Calculate total amount including commission if applicable
        $totalAmount = $transaction->with_commission
            ? $transaction->amount + $transaction->commission_amount
            : $transaction->amount;

        // Update source account (deduct funds)
        if ($transaction->from_account_id) {
            $fromAccount = Account::findOrFail($transaction->from_account_id);
            $fromAccount->balance -= $totalAmount;
            $fromAccount->save();
        }

        // Update destination account (add funds)
        if ($transaction->to_account_id) {
            $toAccount = Account::findOrFail($transaction->to_account_id);
            $toAccount->balance += $transaction->amount; // Only the principal amount, not commission
            $toAccount->save();
        }
    }
}
