<?php

use App\Http\Controllers\AccountManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\OwnerReportController;
use App\Http\Controllers\StaffOrderController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Public Login, Customer Self-Registration & Logout
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Safaricom Daraja M-Pesa Callback (CSRF excluded in bootstrap/app.php)
Route::post('/api/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

// Shared Receipt View (accessible by authenticated staff, customer, or owner)
Route::middleware(['auth'])->group(function () {
    Route::get('/receipts/{receiptId}', [BillingController::class, 'showReceipt'])->name('receipts.show');
});

// Owner Portal Routes
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [OwnerReportController::class, 'dashboard'])->name('dashboard');
    Route::get('/reports/print', [OwnerReportController::class, 'printReport'])->name('reports.print');

    // Service Catalog & Pricing Management
    Route::get('/services', [OwnerReportController::class, 'servicesIndex'])->name('services.index');
    Route::post('/services', [OwnerReportController::class, 'storeService'])->name('services.store');
    Route::put('/services/{serviceId}', [OwnerReportController::class, 'updateService'])->name('services.update');

    // Staff Account Management
    Route::get('/staff', [AccountManagementController::class, 'index'])->name('staff.index');
    Route::post('/staff', [AccountManagementController::class, 'storeStaff'])->name('staff.store');
    Route::patch('/staff/{userId}/toggle', [AccountManagementController::class, 'toggleStaffStatus'])->name('staff.toggle');
    Route::patch('/staff/{userId}/reset-password', [AccountManagementController::class, 'resetStaffPassword'])->name('staff.reset-password');
    Route::patch('/staff/{userId}/reset', [AccountManagementController::class, 'resetStaffPassword'])->name('staff.reset');
});

// Staff Portal Routes
Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffOrderController::class, 'dashboard'])->name('dashboard');

    // Customer Management
    Route::get('/customers/{userId}', [StaffOrderController::class, 'showCustomer'])->name('customers.show');
    Route::post('/customers', [StaffOrderController::class, 'storeCustomer'])->name('customers.store');
    Route::put('/customers/{userId}', [StaffOrderController::class, 'updateCustomer'])->name('customers.update');
    Route::patch('/customers/{userId}/reset-password', [StaffOrderController::class, 'resetCustomerPassword'])->name('customers.reset-password');
    Route::patch('/customers/{userId}/reset', [StaffOrderController::class, 'resetCustomerPassword'])->name('customers.reset');

    // Order Intake & Garment Status Workflow
    Route::post('/orders', [StaffOrderController::class, 'storeOrder'])->name('orders.store');
    Route::patch('/garments/{garmentId}/status', [TrackingController::class, 'updateGarmentStatus'])->name('garments.status');

    // Billing, Payments & Receipts
    Route::post('/orders/{orderId}/bill', [BillingController::class, 'generateBill'])->name('orders.bill');
    Route::post('/orders/{orderId}/bills/generate', [BillingController::class, 'generateBill'])->name('bills.generate');
    Route::post('/bills/{billId}/pay', [BillingController::class, 'recordPayment'])->name('bills.pay');
    Route::post('/bills/{billId}/payments', [BillingController::class, 'recordPayment'])->name('payments.store');
    Route::post('/bills/{billId}/mpesa-stk', [MpesaController::class, 'stkPush'])->name('mpesa.stk');
    Route::post('/bills/{billId}/mpesa', [MpesaController::class, 'stkPush'])->name('mpesa.stk-push');
    Route::get('/receipts/{receiptId}', [BillingController::class, 'showReceipt'])->name('receipts.show');
});

// Customer Portal Routes
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [TrackingController::class, 'customerDashboard'])->name('dashboard');
    Route::get('/delivery', [TrackingController::class, 'deliveryForm'])->name('delivery.form');
    Route::post('/delivery', [TrackingController::class, 'storeDelivery'])->name('delivery.store');
    Route::post('/bills/{billId}/mpesa-stk', [MpesaController::class, 'stkPush'])->name('mpesa.stk');
    Route::get('/receipts/{receiptId}', [BillingController::class, 'showReceipt'])->name('receipts.show');
});