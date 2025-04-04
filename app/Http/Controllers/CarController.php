<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Car;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $query = Car::with(['customerAccount', 'shippingCompany']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $cars = $query->latest()->paginate(15);

        return view('cars.index', compact('cars', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Account::customers()->where('is_active', true)->orderBy('name')->get();
        $shippingCompanies = Account::shippingCompanies()->where('is_active', true)->orderBy('name')->get();

        return view('cars.create', compact('customers', 'shippingCompanies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vin' => 'required|string|max:17|unique:cars',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'intermediary_profit' => 'nullable|numeric|min:0',
            'auction_name' => 'nullable|string|max:255',
            'auction_lot_number' => 'nullable|string|max:100',
            'customer_account_id' => 'nullable|exists:accounts,id',
            'shipping_company_id' => 'nullable|exists:accounts,id',
            'status' => 'required|in:purchased,shipped,delivered,sold',
            'purchase_date' => 'required|date',
            'shipping_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $car = Car::create($request->all());

            // If a customer is specified, create a purchase transaction
            if ($request->customer_account_id) {
                $intermediaryAccount = Account::where('type', 'intermediary')->first();

                if ($intermediaryAccount) {
                    // Create a transaction for the car purchase
                    Transaction::create([
                        'transaction_number' => 'CAR-PURCH-' . $car->id,
                        'type' => 'purchase',
                        'from_account_id' => $intermediaryAccount->id,
                        'to_account_id' => null, // Auction/dealer not in system
                        'car_id' => $car->id,
                        'amount' => $request->purchase_price,
                        'commission_amount' => 0,
                        'with_commission' => false,
                        'transaction_date' => $request->purchase_date,
                        'description' => 'Purchase of ' . $car->make . ' ' . $car->model . ' (' . $car->year . ')',
                        'status' => 'completed',
                        'created_by' => Auth::id(),
                    ]);

                    // Update intermediary account balance
                    $intermediaryAccount->balance -= $request->purchase_price;
                    $intermediaryAccount->save();
                }
            }

            DB::commit();

            return redirect()->route('cars.show', $car)
                ->with('success', 'Car created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to create car: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Car $car)
    {
        // Load car transactions
        $transactions = Transaction::where('car_id', $car->id)->latest()->get();

        // Calculate car statistics and financials
        $stats = [
            'total_cost' => $car->getTotalCost(),
            'profit' => $car->getProfit(),
            'days_since_purchase' => now()->diffInDays($car->purchase_date),
        ];

        return view('cars.show', compact('car', 'transactions', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Car $car)
    {
        $customers = Account::customers()->where('is_active', true)->orderBy('name')->get();
        $shippingCompanies = Account::shippingCompanies()->where('is_active', true)->orderBy('name')->get();

        return view('cars.edit', compact('car', 'customers', 'shippingCompanies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Car $car)
    {
        $request->validate([
            'vin' => 'required|string|max:17|unique:cars,vin,' . $car->id,
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'intermediary_profit' => 'nullable|numeric|min:0',
            'auction_name' => 'nullable|string|max:255',
            'auction_lot_number' => 'nullable|string|max:100',
            'customer_account_id' => 'nullable|exists:accounts,id',
            'shipping_company_id' => 'nullable|exists:accounts,id',
            'status' => 'required|in:purchased,shipped,delivered,sold',
            'purchase_date' => 'required|date',
            'shipping_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $car->update($request->all());

        return redirect()->route('cars.show', $car)
            ->with('success', 'Car updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Car $car)
    {
        // Check if car has transactions before deletion
        if ($car->transactions()->count() > 0) {
            return back()->with('error', 'Cannot delete car with associated transactions');
        }

        $car->delete();

        return redirect()->route('cars.index')
            ->with('success', 'Car deleted successfully');
    }

    /**
     * Record shipping for a car
     */
    public function recordShipping(Request $request, Car $car)
    {
        $request->validate([
            'shipping_company_id' => 'required|exists:accounts,id',
            'shipping_cost' => 'required|numeric|min:0',
            'shipping_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Update car details
            $car->update([
                'shipping_company_id' => $request->shipping_company_id,
                'shipping_cost' => $request->shipping_cost,
                'shipping_date' => $request->shipping_date,
                'status' => 'shipped',
                'notes' => $request->notes ? $car->notes . "\n" . $request->notes : $car->notes,
            ]);

            // Create a transaction for the shipping cost
            $intermediaryAccount = Account::where('type', 'intermediary')->first();
            $shippingCompany = Account::findOrFail($request->shipping_company_id);

            if ($intermediaryAccount) {
                Transaction::create([
                    'transaction_number' => 'CAR-SHIP-' . $car->id,
                    'type' => 'shipping',
                    'from_account_id' => $intermediaryAccount->id,
                    'to_account_id' => $shippingCompany->id,
                    'car_id' => $car->id,
                    'amount' => $request->shipping_cost,
                    'commission_amount' => 0,
                    'with_commission' => false,
                    'transaction_date' => $request->shipping_date,
                    'description' => 'Shipping cost for ' . $car->make . ' ' . $car->model . ' (' . $car->year . ')',
                    'status' => 'completed',
                    'created_by' => Auth::id(),
                ]);

                // Update account balances
                $intermediaryAccount->balance -= $request->shipping_cost;
                $intermediaryAccount->save();

                $shippingCompany->balance += $request->shipping_cost;
                $shippingCompany->save();
            }

            DB::commit();

            return redirect()->route('cars.show', $car)
                ->with('success', 'Shipping details recorded successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to record shipping: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Record delivery of a car
     */
    public function recordDelivery(Request $request, Car $car)
    {
        $request->validate([
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $car->update([
            'delivery_date' => $request->delivery_date,
            'status' => 'delivered',
            'notes' => $request->notes ? $car->notes . "\n" . $request->notes : $car->notes,
        ]);

        return redirect()->route('cars.show', $car)
            ->with('success', 'Delivery recorded successfully');
    }

    /**
     * Record sale of a car
     */
    public function recordSale(Request $request, Car $car)
    {
        $request->validate([
            'customer_account_id' => 'required|exists:accounts,id',
            'selling_price' => 'required|numeric|min:0',
            'intermediary_profit' => 'nullable|numeric|min:0',
            'sale_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Update car details
            $car->update([
                'customer_account_id' => $request->customer_account_id,
                'selling_price' => $request->selling_price,
                'intermediary_profit' => $request->intermediary_profit,
                'status' => 'sold',
                'notes' => $request->notes ? $car->notes . "\n" . $request->notes : $car->notes,
            ]);

            // Create a transaction for the car sale
            $intermediaryAccount = Account::where('type', 'intermediary')->first();
            $customerAccount = Account::findOrFail($request->customer_account_id);

            if ($intermediaryAccount) {
                Transaction::create([
                    'transaction_number' => 'CAR-SALE-' . $car->id,
                    'type' => 'sale',
                    'from_account_id' => $customerAccount->id,
                    'to_account_id' => $intermediaryAccount->id,
                    'car_id' => $car->id,
                    'amount' => $request->selling_price,
                    'commission_amount' => 0,
                    'with_commission' => false,
                    'transaction_date' => $request->sale_date,
                    'description' => 'Sale of ' . $car->make . ' ' . $car->model . ' (' . $car->year . ')',
                    'status' => 'completed',
                    'created_by' => Auth::id(),
                ]);

                // Update account balances
                $customerAccount->balance -= $request->selling_price;
                $customerAccount->save();

                $intermediaryAccount->balance += $request->selling_price;
                $intermediaryAccount->save();
            }

            DB::commit();

            return redirect()->route('cars.show', $car)
                ->with('success', 'Sale recorded successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to record sale: ' . $e->getMessage())->withInput();
        }
    }
}
