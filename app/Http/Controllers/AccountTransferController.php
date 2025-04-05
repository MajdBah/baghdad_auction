<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountTransferController extends Controller
{
    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        return view('accounts.transfers.create', compact('accounts'));
    }

    /**
     * Store a newly created transfer in storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'with_commission' => 'boolean',
            'commission_percentage' => 'nullable|required_if:with_commission,1|numeric|min:0|max:100',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $fromAccount = Account::findOrFail($request->from_account_id);
            $toAccount = Account::findOrFail($request->to_account_id);
            $amount = $request->amount;
            $withCommission = $request->has('with_commission') && $request->with_commission;
            $commissionPercentage = $withCommission ? $request->commission_percentage : 0;
            $commissionAmount = $withCommission ? $amount * ($commissionPercentage / 100) : 0;
            $totalAmount = $amount + $commissionAmount;

            // The check for sufficient funds is removed to allow negative balances
            // Create transaction
            $transactionNumber = 'TRF-' . strtoupper(Str::random(8));

            // Create transaction
            $transaction = Transaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'transfer',
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount,
                'commission_amount' => $commissionAmount,
                'with_commission' => $withCommission,
                'reference_number' => $request->reference_number,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);

            // Update account balances
            $fromAccount->balance -= $totalAmount;

            // Log the negative balance if it occurs
            if ($fromAccount->balance < 0) {
                \Log::info("Account {$fromAccount->name} ({$fromAccount->account_number}) has a negative balance of {$fromAccount->balance} after transfer {$transactionNumber}");
            }

            $fromAccount->save();

            $toAccount->balance += $amount; // Only the amount without commission
            $toAccount->save();

            // If commission exists, update intermediary account
            if ($withCommission) {
                $intermediaryAccount = Account::where('type', 'intermediary')->first();
                if ($intermediaryAccount) {
                    $intermediaryAccount->balance += $commissionAmount;
                    $intermediaryAccount->save();
                }
            }

            DB::commit();

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Transfer processed successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Failed to process transfer: ' . $e->getMessage());
        }
    }
}
