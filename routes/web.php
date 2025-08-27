<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Http\Controllers\CheckLogin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\ClientUpload;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\PrescriptionItemController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StockController;

Route::get('/', fn() => redirect('/admin/login'));

// Admin authentication routes (no middleware needed for login page)
Route::get('/admin/login', [CheckLogin::class, 'show'])->name('login');
Route::post('/admin/login', [CheckLogin::class, 'check'])->name('admin.login');
Route::post('/admin/logout', [CheckLogin::class, 'logout'])->name('admin.logout');

// Protected admin routes
Route::middleware(['auth', 'admin'])->group(function () {
    // Main dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin prefix routes
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', fn() => redirect()->route('dashboard'))->name('admin.dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('admin.dashboard.stats');
        Route::get('/dashboard/recent-activity', [DashboardController::class, 'getRecentActivity'])->name('admin.dashboard.recent');
        Route::get('/dashboard/top-products', [DashboardController::class, 'getTopProducts'])->name('admin.dashboard.products');
        Route::get('/dashboard/critical-alerts', [DashboardController::class, 'getCriticalAlerts'])->name('admin.dashboard.alerts');
        Route::get('/dashboard/weekly-summary', [DashboardController::class, 'getWeeklySummary'])->name('admin.dashboard.weekly');
    });
    Route::get('/inventory-alerts-check', [DashboardController::class, 'checkInventoryAlerts'])->name('inventory.alerts.check');
});

Route::middleware(['auth', 'admin'])->group(function () {
    // Admin Profile Routes
    Route::prefix('admin')->group(function () {
        Route::get('/profile', [CheckLogin::class, 'showProfile'])->name('admin.profile');
        Route::get('/profile/edit', [CheckLogin::class, 'editProfile'])->name('admin.profile.edit');
        Route::post('/profile/edit', [CheckLogin::class, 'updateProfile'])->name('admin.profile.update');
        Route::get('/profile/change-password', [CheckLogin::class, 'showChangePassword'])->name('admin.profile.change-password');
        Route::post('/profile/change-password', [CheckLogin::class, 'updatePassword'])->name('admin.profile.update-password');
        Route::get('/settings/permissions', [CheckLogin::class, 'showPermissions'])->name('admin.settings.permissions');
    });
});

Route::prefix('dashboard/products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
});

Route::prefix('dashboard')->middleware('auth')->group(function () {

    // Product resource routes
    Route::resource('products', ProductController::class)->except(['show']);

    // Product detail routes
    Route::get('/products/{id}/details', [ProductController::class, 'productDetails'])->name('products.details');
    Route::get('/products/{id}/stock-movements', [ProductController::class, 'stockMovements'])->name('products.stock-movements');

    // Batch-related routes
    Route::get('/products/{id}/batches', [ProductController::class, 'onlyshowBatches'])->name('products.batches.show');
    Route::post('/products/{id}/batches', [ProductController::class, 'addBatch'])->name('products.batches.store');

    // Batch management routes
    Route::post('/batches/{id}/action', [ProductController::class, 'batchAction'])->name('batches.action');
    Route::get('/batches/{id}/history', [ProductController::class, 'batchHistory'])->name('batches.history');

    // Process sale
    Route::post('/products/{id}/sale', [ProductController::class, 'processSale'])->name('products.sale');

});

Route::middleware(['auth'])->group(function () {
    // Inventory routes
    Route::get('/dashboard/inventory', [InventoryController::class, 'index'])->name('inventory.index');

    // Stock management routes
    Route::post('/dashboard/inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
    Route::post('/dashboard/inventory/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');
    Route::get('/dashboard/inventory/stock-out-reasons', [InventoryController::class, 'getStockOutReasons'])->name('inventory.stock-out-reasons');

    // Batch management routes
    Route::post('/dashboard/products/{product}/batches', [InventoryController::class, 'addBatch'])->name('products.add-batch');
    Route::get('/dashboard/inventory/{product}/batches', [ProductController::class, 'showBatches'])->name('products.batches');

    // Batch actions route (for marking expired, adjusting quantities, etc.)
    Route::post('/dashboard/products/batches/{batch}/action', [ProductController::class, 'batchAction'])->name('products.batch-action');
});

// Reports and Prescriptions (Admin Views)
Route::get('/dashboard/reports', [ReportsController::class, 'index'])->name('reports.index');
Route::post('/admin/reports/generate', [ReportsController::class, 'generate'])->name('admin.reports.generate');
Route::post('/admin/reports/export-pdf', [ReportsController::class, 'exportPDF'])->name('admin.reports.pdf');
Route::post('/admin/reports/export-excel', [ReportsController::class, 'exportExcel'])->name('admin.reports.excel');

 Route::get('/POS', fn() => redirect()->route('POS'))->name('POS.index');

Route::get('/dashboard/customer', [AdminCustomerController::class, 'index'])->name('customer.index');
Route::group(['prefix' => 'admin'], function () {
    // Customer management routes
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('admin.customers.index');
    Route::get('/customers/search', [AdminCustomerController::class, 'search'])->name('admin.customers.search');
    Route::get('/customers/{id}', [AdminCustomerController::class, 'show'])->name('admin.customers.show');
    Route::patch('/customers/{customer}/restrict', [AdminCustomerController::class, 'restrict']);
    Route::patch('/customers/{customer}/toggle-activation', [AdminCustomerController::class, 'toggleActivation']);
    Route::delete('/customers/{customer}', [AdminCustomerController::class, 'destroy']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('/notifications/all', [NotificationController::class, 'showAll'])->name('notifications.all');
    Route::post('/notifications/bulk-delete', [NotificationController::class, 'bulkDelete']);
    Route::post('/notifications/clear-read', [NotificationController::class, 'clearRead']);
});

// Order Management Routes
Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
Route::post('/orders/{id}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
Route::post('/orders/{id}/partial-approve', [AdminOrderController::class, 'partialApprove'])->name('orders.partial-approve');
Route::post('/orders/{id}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/prescriptions/{id}/items', [AdminOrderController::class, 'saveSelection'])->name('prescriptions.save-items');
Route::get('/prescriptions/{id}/items', [AdminOrderController::class, 'getPrescriptionItems'])->name('prescriptions.get-items');
// Order summary and completion
Route::get('/orders/{id}/summary', [AdminOrderController::class, 'getOrderSummary'])->name('orders.summary');
Route::post('/orders/{id}/complete', [AdminOrderController::class, 'completeOrder'])->name('orders.complete');
Route::get('/debug/prescription/{id}', [AdminOrderController::class, 'debugPrescription']);
Route::get('/sales', [AdminOrderController::class, 'sales'])->name('sales.index');

Route::middleware(['auth', 'admin'])->group(function () {
    // View encrypted prescription file (for images)
    Route::get('/prescription/file/view/{prescriptionId}', [ClientUpload::class, 'viewPrescriptionFile'])
        ->name('prescription.file.view');

    // Download encrypted prescription file
    Route::get('/prescription/file/download/{prescriptionId}', [ClientUpload::class, 'downloadPrescriptionFile'])
        ->name('prescription.file.download');

    // Get file metadata (optional - for additional info)
    Route::get('/prescription/file/metadata/{prescriptionId}', [ClientUpload::class, 'getPrescriptionMetadata'])
        ->name('prescription.file.metadata');
});


Route::prefix('dashboard/suppliers')->name('suppliers.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/create', [SupplierController::class, 'create'])->name('create');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [SupplierController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SupplierController::class, 'update'])->name('update');
    Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('destroy');
});

// Customer Authentication (Signup & Login)
Route::get('/login', [AuthController::class, 'showLoginForm1'])->name('login.form');
Route::post('/login', [AuthController::class, 'login1'])->name('customer.login');
Route::get('/signup/step1', [AuthController::class, 'showSignupStepOne'])->name('signup.step_one');
Route::post('/signup/step1', [AuthController::class, 'handleSignupStepOne'])->name('signup.step_one.submit');
Route::get('/signup/step2', [AuthController::class, 'showSignupStepTwo'])->name('signup.step_two');
Route::post('/signup/step2', [AuthController::class, 'handleSignupStepTwo'])->name('signup.step_two.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('customer.logout');

// Customer Views
Route::get('/home', [AuthController::class, 'Home'])->name('home')->middleware('auth:customer');
Route::get('/home/products', [ProductController::class, 'customerIndex'])->name('customer.products');
Route::get('/home/contact-us', fn() => view('client.contact-us'))->name('contact-us');
Route::get('/profile', [AuthController::class, 'show'])->name('customer.profile');

Route::middleware(['auth:customer'])->group(function () {

    // Customer Notifications
    Route::prefix('home/notifications')->name('customer.notifications.')->group(function () {
        Route::get('/', [CustomerNotificationController::class, 'index'])->name('index');
        Route::post('/read/{id}', [CustomerNotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [CustomerNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/unread-count', [CustomerNotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::delete('/{id}', [CustomerNotificationController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/home/uploads', [ClientUpload::class, 'showUploadForm'])->name('uploads');
    Route::get('/upload-prescription', [ClientUpload::class, 'showUploadForm'])->name('prescription.upload.form');
    Route::post('/upload-prescription', [ClientUpload::class, 'handleUpload'])->name('prescription.upload');
    Route::get('/preorder/status/{token}', [ClientUpload::class, 'viewStatus']);
    Route::get('/preorder/validate/{token}', [ClientUpload::class, 'validatePreorder'])->name('preorder.validate');
    Route::get('/prescription/qr/{id}', [ClientUpload::class, 'showQrCode'])->name('prescription.qr');
});

// Prescription Item Management
Route::post('/prescriptions/{id}/items', [PrescriptionItemController::class, 'saveItems'])->name('prescription.items.save');
Route::get('/prescriptions/{id}/items', [PrescriptionItemController::class, 'getItems']);


Route::get('/feedback', function () {
    return view('client.feedback');
});


Route::get('/reset-password', fn() => view('reset-password'))->name('password.reset');

Route::post('/send-code', function (Request $request) {
    $request->validate(['email' => 'required|email']);
    $code = rand(1000, 9999);

    Session::put('reset_code', $code);
    Session::put('reset_email', $request->input('email'));

    return back()->with('status', 'We sent a 4-digit code: ' . $code); // Demo only
})->name('password.send-code');

//Submit new password with code
Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'code' => 'required',
        'password' => 'required|confirmed'
    ]);

    if (
        $request->input('email') === Session::get('reset_email') &&
        $request->input('code') == Session::get('reset_code')
    ) {
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            $user->password = Hash::make($request->input('password'));
            $user->save();
            Session::forget(['reset_code', 'reset_email']);
            return redirect('/login')->with('status', 'Password successfully updated!');
        } else {
            return back()->with('error', 'User not found.');
        }
    } else {
        return back()->with('error', 'Code doesn\'t match or email is incorrect.');
    }
})->name('password.update');
