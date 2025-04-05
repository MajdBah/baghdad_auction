<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountTransferController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Registration Routes
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Password Reset Routes
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Dashboard Route (no auth required to see dashboard)
Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// Routes protected by authentication
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Account routes
    Route::resource('accounts', AccountController::class);
    Route::get('accounts/{account}/statement', [AccountController::class, 'statement'])->name('accounts.statement');

    // Transaction routes
    Route::resource('transactions', TransactionController::class);
    Route::get('transactions/{transaction}/process', [TransactionController::class, 'showProcessForm'])->name('transactions.process');
    Route::patch('transactions/{transaction}/process', [TransactionController::class, 'process'])->name('transactions.do_process');
    Route::get('transactions/{transaction}/cancel', [TransactionController::class, 'showCancelForm'])->name('transactions.cancel');
    Route::patch('transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.do_cancel');
    Route::get('transfer', [TransactionController::class, 'transferForm'])->name('transactions.transfer_form');
    Route::post('transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');

    // Account Transfers
    Route::get('transfers/create', [AccountTransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [AccountTransferController::class, 'store'])->name('transfers.store');

    // Car routes
    Route::resource('cars', CarController::class);
    Route::post('cars/{car}/shipping', [CarController::class, 'recordShipping'])->name('cars.shipping');
    Route::post('cars/{car}/delivery', [CarController::class, 'recordDelivery'])->name('cars.delivery');
    Route::post('cars/{car}/sale', [CarController::class, 'recordSale'])->name('cars.sale');

    // Invoice routes
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/payment', [InvoiceController::class, 'showPaymentForm'])->name('invoices.payment_form');
    Route::post('invoices/{invoice}/payment', [InvoiceController::class, 'recordPayment'])->name('invoices.record_payment');
    Route::get('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email');
    Route::get('invoices/{invoice}/items', [InvoiceController::class, 'showItems'])->name('invoices.items');

    // Report routes
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/account-statement', [ReportController::class, 'accountStatement'])->name('reports.account_statement');
    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit_loss');
    Route::get('reports/car-inventory', [ReportController::class, 'carInventory'])->name('reports.car_inventory');
    Route::get('reports/outstanding-balances', [ReportController::class, 'outstandingBalances'])->name('reports.outstanding_balances');
    Route::get('reports/shipping-companies', [ReportController::class, 'shippingCompaniesPerformance'])->name('reports.shipping_companies');
    Route::get('reports/commission', [ReportController::class, 'commissionReport'])->name('reports.commission');

    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('roles', RoleController::class);
    });
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
