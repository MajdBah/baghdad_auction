<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'all');
        $query = Account::query();

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $accounts = $query->orderBy('name')->paginate(10);

        return view('accounts.index', compact('accounts', 'type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,shipping_company,intermediary',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Generate a unique account number
        $accountNumber = 'ACC-' . strtoupper(Str::random(8));

        $account = Account::create([
            'name' => $request->name,
            'account_number' => $accountNumber,
            'type' => $request->type,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'notes' => $request->notes,
            'user_id' => Auth::id(),
            'balance' => 0,
            'is_active' => true,
        ]);

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Account created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        // Load account transactions
        $incomingTransactions = $account->incomingTransactions()->latest()->take(10)->get();
        $outgoingTransactions = $account->outgoingTransactions()->latest()->take(10)->get();

        // Load account invoices
        $invoices = $account->invoices()->latest()->take(10)->get();

        // Calculate account statistics
        $stats = [
            'total_transactions' => $incomingTransactions->count() + $outgoingTransactions->count(),
            'total_invoices' => $invoices->count(),
            'unpaid_invoices' => $account->invoices()->whereIn('status', ['draft', 'issued', 'partially_paid', 'overdue'])->count(),
        ];

        return view('accounts.show', compact('account', 'incomingTransactions', 'outgoingTransactions', 'invoices', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account)
    {
        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $account->update($request->all());

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Account updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        // Check if account has transactions or invoices before deletion
        if ($account->incomingTransactions()->count() > 0 ||
            $account->outgoingTransactions()->count() > 0 ||
            $account->invoices()->count() > 0) {
            return back()->with('error', 'Cannot delete account with associated transactions or invoices');
        }

        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', 'Account deleted successfully');
    }

    /**
     * Display the account statement
     */
    public function statement(Account $account, Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(1)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Get all transactions for this account within date range
        $incomingTransactions = $account->incomingTransactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $outgoingTransactions = $account->outgoingTransactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        // Get all invoices for this account within date range
        $invoices = $account->invoices()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get();

        // Merge and sort all transactions by date
        $allTransactions = $incomingTransactions->concat($outgoingTransactions)
            ->sortBy('transaction_date');

        return view('accounts.statement', compact('account', 'allTransactions', 'invoices', 'startDate', 'endDate'));
    }
}
