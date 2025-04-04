<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display the main reports dashboard
     */
    public function index()
    {
        // Get summary statistics
        $stats = [
            'total_cars' => Car::count(),
            'cars_purchased' => Car::where('status', 'purchased')->count(),
            'cars_shipped' => Car::where('status', 'shipped')->count(),
            'cars_delivered' => Car::where('status', 'delivered')->count(),
            'cars_sold' => Car::where('status', 'sold')->count(),
            'total_invoices' => Invoice::count(),
            'unpaid_invoices' => Invoice::whereIn('status', ['issued', 'partially_paid'])->count(),
            'total_transactions' => Transaction::count(),
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * Generate account statement for a specific account
     */
    public function accountStatement(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $account = Account::findOrFail($request->account_id);
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Get opening balance (sum of transactions before start date)
        $openingDebit = Transaction::where('from_account_id', $account->id)
            ->where('transaction_date', '<', $startDate)
            ->sum('amount');

        $openingCredit = Transaction::where('to_account_id', $account->id)
            ->where('transaction_date', '<', $startDate)
            ->sum('amount');

        $openingBalance = $openingCredit - $openingDebit;

        // Get transactions for the period
        $transactions = Transaction::where(function($query) use ($account) {
                $query->where('from_account_id', $account->id)
                    ->orWhere('to_account_id', $account->id);
            })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        // Get invoices for the period
        $invoices = Invoice::where('account_id', $account->id)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->orderBy('issue_date')
            ->get();

        // Calculate current balance
        $currentBalance = $openingBalance;

        // Transform transactions for statement
        $statement = [];
        foreach ($transactions as $transaction) {
            $amount = $transaction->amount;
            $isDebit = $transaction->from_account_id == $account->id;

            if ($isDebit) {
                $currentBalance -= $amount;
                $debit = $amount;
                $credit = 0;
            } else {
                $currentBalance += $amount;
                $debit = 0;
                $credit = $amount;
            }

            $statement[] = [
                'date' => $transaction->transaction_date,
                'description' => $transaction->description,
                'reference' => $transaction->transaction_number,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $currentBalance,
                'type' => 'transaction'
            ];
        }

        return view('reports.account_statement', compact(
            'account',
            'startDate',
            'endDate',
            'openingBalance',
            'currentBalance',
            'statement',
            'transactions',
            'invoices'
        ));
    }

    /**
     * Show profit and loss report
     */
    public function profitLoss(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now());

        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!$endDate instanceof Carbon) {
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        // Calculate revenue
        $revenue = [
            'car_sales' => Transaction::where('type', 'sale')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount'),
            'shipping_fees' => Transaction::where('type', 'shipping')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount'),
            'commissions' => Transaction::where('with_commission', true)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('commission_amount'),
        ];

        $totalRevenue = array_sum($revenue);

        // Calculate expenses
        $expenses = [
            'car_purchases' => Transaction::where('type', 'purchase')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount'),
            'shipping_costs' => Car::whereBetween('shipping_date', [$startDate, $endDate])
                ->sum('shipping_cost'),
            'operational_costs' => 0, // You might need to add a new model for operational costs
        ];

        $totalExpenses = array_sum($expenses);

        // Calculate profit
        $grossProfit = $totalRevenue - $totalExpenses;

        // Get profit by car
        $carProfits = Car::where('status', 'sold')
            ->whereBetween('shipping_date', [$startDate, $endDate])
            ->get()
            ->map(function($car) {
                return [
                    'id' => $car->id,
                    'name' => $car->make . ' ' . $car->model . ' (' . $car->year . ')',
                    'purchase_price' => $car->purchase_price,
                    'shipping_cost' => $car->shipping_cost,
                    'selling_price' => $car->selling_price,
                    'profit' => $car->getProfit(),
                ];
            });

        return view('reports.profit_loss', compact(
            'startDate',
            'endDate',
            'revenue',
            'totalRevenue',
            'expenses',
            'totalExpenses',
            'grossProfit',
            'carProfits'
        ));
    }

    /**
     * Show aging receivables report
     */
    public function agingReceivables()
    {
        $today = Carbon::now();

        $accounts = Account::customers()->with(['invoices' => function($query) {
            $query->whereIn('status', ['issued', 'partially_paid']);
        }])->get();

        $agingData = [];

        foreach ($accounts as $account) {
            $current = 0;
            $days30 = 0;
            $days60 = 0;
            $days90 = 0;
            $days90Plus = 0;

            foreach ($account->invoices as $invoice) {
                $daysOverdue = $today->diffInDays($invoice->due_date, false);

                if ($daysOverdue <= 0) {
                    $current += $invoice->balance;
                } elseif ($daysOverdue <= 30) {
                    $days30 += $invoice->balance;
                } elseif ($daysOverdue <= 60) {
                    $days60 += $invoice->balance;
                } elseif ($daysOverdue <= 90) {
                    $days90 += $invoice->balance;
                } else {
                    $days90Plus += $invoice->balance;
                }
            }

            $total = $current + $days30 + $days60 + $days90 + $days90Plus;

            if ($total > 0) {
                $agingData[] = [
                    'account' => $account,
                    'current' => $current,
                    'days30' => $days30,
                    'days60' => $days60,
                    'days90' => $days90,
                    'days90Plus' => $days90Plus,
                    'total' => $total
                ];
            }
        }

        return view('reports.aging_receivables', compact('agingData'));
    }

    /**
     * Show detailed car profitability report
     */
    public function carProfitability(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonths(3));
        $endDate = $request->input('end_date', Carbon::now());

        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!$endDate instanceof Carbon) {
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        // Get all cars sold in the date range
        $cars = Car::where('status', 'sold')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->with(['customerAccount', 'shippingCompany', 'transactions'])
            ->get();

        $profitReport = [];

        foreach ($cars as $car) {
            $profitReport[] = [
                'car' => $car,
                'customer' => $car->customerAccount ? $car->customerAccount->name : 'N/A',
                'shipping_company' => $car->shippingCompany ? $car->shippingCompany->name : 'N/A',
                'purchase_price' => $car->purchase_price,
                'shipping_cost' => $car->shipping_cost,
                'total_cost' => $car->getTotalCost(),
                'selling_price' => $car->selling_price,
                'profit' => $car->getProfit(),
                'profit_percentage' => $car->getTotalCost() > 0 ? ($car->getProfit() / $car->getTotalCost()) * 100 : 0,
                'days_to_sell' => $car->purchase_date->diffInDays($car->sale_date ?? Carbon::now()),
            ];
        }

        return view('reports.car_profitability', compact('profitReport', 'startDate', 'endDate'));
    }

    /**
     * Generate commission report
     */
    public function commissionReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now());

        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!$endDate instanceof Carbon) {
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        // Get all transactions with commission in the date range
        $transactions = Transaction::where('with_commission', true)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['fromAccount', 'toAccount', 'car'])
            ->get();

        $totalCommission = $transactions->sum('commission_amount');

        return view('reports.commission', compact('transactions', 'totalCommission', 'startDate', 'endDate'));
    }
}
