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
use App\Http\Controllers\PosController;

Route::get('/', fn() => redirect('/admin/login'));

Route::get('/admin/login', [CheckLogin::class, 'show'])->name('login');
Route::post('/admin/login', [CheckLogin::class, 'check'])->name('admin.login');
Route::post('/admin/logout', [CheckLogin::class, 'logout'])->name('admin.logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', fn() => redirect()->route('dashboard'))->name('admin.dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('admin.dashboard.stats');
        Route::get('/dashboard/recent-activity', [DashboardController::class, 'getRecentActivity'])->name('admin.dashboard.recent');
        Route::get('/dashboard/top-products', [DashboardController::class, 'getTopProducts'])->name('admin.dashboard.products');
        Route::get('/dashboard/critical-alerts', [DashboardController::class, 'getCriticalAlerts'])->name('admin.dashboard.alerts');
        Route::get('/dashboard/weekly-summary', [DashboardController::class, 'getWeeklySummary'])->name('admin.dashboard.weekly');
        Route::get('/dashboard/expiring-products', [DashboardController::class, 'getExpiringProducts'])->name('admin.dashboard.expiring-products');
        Route::get('/dashboard/low-stock', [DashboardController::class, 'getLowStockProducts'])->name('admin.dashboard.low-stock-products');
        Route::get('/dashboard/out-of-stock', [DashboardController::class, 'getOutOfStockProducts'])->name('admin.dashboard.out-of-stock-products');
        Route::get('/dashboard/urgent-items', [DashboardController::class, 'getUrgentItems'])->name('admin.dashboard.urgent');
        Route::get('/dashboard/inventory-metrics', [DashboardController::class, 'getInventoryMetrics'])->name('admin.dashboard.inventory-metrics');
        Route::get('/dashboard/activity-details/{type}', [DashboardController::class, 'getActivityDetails'])->name('admin.dashboard.activity-details');
    });

    Route::get('/inventory-alerts-check', [DashboardController::class, 'checkInventoryAlerts'])->name('inventory.alerts.check');
    Route::get('/inventory-stock-details/{productId}', [DashboardController::class, 'getProductStockDetails'])->name('product.stock.details');
});



Route::middleware(['auth'])->group(function () {
    // Main POS interface
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');

    // AJAX endpoints for POS
    Route::post('/pos/search', [PosController::class, 'searchProducts'])->name('pos.search');
    Route::get('/pos/product/{id}', [PosController::class, 'getProduct'])->name('pos.product');
    Route::post('/pos/process-transaction', [PosController::class, 'processTransaction'])->name('pos.process');
    Route::post('/pos/cancel-transaction', [PosController::class, 'cancelTransaction'])->name('pos.cancel');
    Route::get('/pos/receipt/{transactionId}', [PosController::class, 'getReceipt'])->name('pos.receipt');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/profile', [CheckLogin::class, 'showProfile'])->name('admin.profile');
        Route::get('/profile/edit', [CheckLogin::class, 'editProfile'])->name('admin.profile.edit');
        Route::post('/profile/edit', [CheckLogin::class, 'updateProfile'])->name('admin.profile.update');
        Route::get('/profile/change-password', [CheckLogin::class, 'showChangePassword'])->name('admin.profile.change-password');
        Route::post('/profile/change-password', [CheckLogin::class, 'updatePassword'])->name('admin.profile.update-password');
        Route::get('/settings/permissions', [CheckLogin::class, 'showPermissions'])->name('admin.settings.permissions');
    });
});

Route::prefix('dashboard/products')->middleware(['auth', 'admin'])->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/details', [ProductController::class, 'productDetails'])->name('details');
    Route::get('/{id}/stock-movements', [ProductController::class, 'stockMovements'])->name('stock-movements');
    Route::get('/{id}/batches', [ProductController::class, 'onlyshowBatches'])->name('batches.show');
    Route::post('/{id}/batches', [ProductController::class, 'addBatch'])->name('batches.store');
    Route::post('/{id}/sale', [ProductController::class, 'processSale'])->name('sale');
});

Route::prefix('dashboard/batches')->middleware(['auth', 'admin'])->name('batches.')->group(function () {
    Route::post('/{id}/action', [ProductController::class, 'batchAction'])->name('action');
    Route::get('/{id}/history', [ProductController::class, 'batchHistory'])->name('history');
});

Route::middleware(['auth', 'admin'])->group(function () {
// Inventory routes
Route::get('/dashboard/inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('/dashboard/inventory/{product}/batches', [InventoryController::class, 'viewBatches'])->name('inventory.viewBatches');
Route::post('/dashboard/inventory/{product}/batches', [InventoryController::class, 'addBatch'])->name('inventory.addBatch');
Route::post('/dashboard/inventory/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stockOut');
Route::get('/dashboard/inventory/stock-status/{product}', [InventoryController::class, 'getProductStockStatus'])->name('inventory.stockStatus');
Route::get('/dashboard/inventory/expired-batches', [InventoryController::class, 'getExpiredBatches'])->name('inventory.expiredBatches');
Route::post('/inventory/batch/{batch}/add-stock', [InventoryController::class, 'addStockToBatch'])
    ->name('inventory.addStockToBatch');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::post('/admin/reports/generate', [ReportsController::class, 'generate'])->name('admin.reports.generate');
    Route::post('/admin/reports/export-pdf', [ReportsController::class, 'exportPDF'])->name('admin.reports.pdf');
    Route::post('/admin/reports/export-excel', [ReportsController::class, 'exportExcel'])->name('admin.reports.excel');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard/customer', [AdminCustomerController::class, 'index'])->name('customer.index');
    Route::prefix('admin')->group(function () {
        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('admin.customers.index');
        Route::get('/customers/search', [AdminCustomerController::class, 'search'])->name('admin.customers.search');
        Route::get('/customers/{id}', [AdminCustomerController::class, 'show'])->name('admin.customers.show');
        Route::patch('/customers/{customer}/restrict', [AdminCustomerController::class, 'restrict']);
        Route::patch('/customers/{customer}/toggle-activation', [AdminCustomerController::class, 'toggleActivation']);
        Route::delete('/customers/{customer}', [AdminCustomerController::class, 'destroy']);
    });
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('/notifications/all', [NotificationController::class, 'showAll'])->name('notifications.all');
    Route::post('/notifications/bulk-delete', [NotificationController::class, 'bulkDelete']);
    Route::post('/notifications/clear-read', [NotificationController::class, 'clearRead']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::post('/admin/orders/{id}/approve', [AdminOrderController::class, 'approve']);
    Route::post('/admin/orders/{id}/cancel', [AdminOrderController::class, 'cancel']);
    Route::post('/prescriptions/{prescriptionId}/save-selection', [AdminOrderController::class, 'saveSelection']);
    Route::get('/prescriptions/{prescriptionId}/items', [AdminOrderController::class, 'getPrescriptionItems']);
    Route::get('/orders/{id}/summary', [AdminOrderController::class, 'getOrderSummary'])->name('orders.summary');
    Route::post('/orders/{id}/complete', [AdminOrderController::class, 'completeOrder'])->name('orders.complete');
    Route::get('/debug/prescription/{id}', [AdminOrderController::class, 'debugPrescription']);
    Route::get('/admin/sales', [AdminOrderController::class, 'sales'])->name('sales.index');
   Route::get('/admin/sales/{id}/details', [AdminOrderController::class, 'getSaleDetails']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/prescription/file/view/{prescriptionId}', [ClientUpload::class, 'viewPrescriptionFile'])->name('prescription.file.view');
    Route::get('/prescription/file/download/{prescriptionId}', [ClientUpload::class, 'downloadPrescriptionFile'])->name('prescription.file.download');
    Route::get('/prescription/file/metadata/{prescriptionId}', [ClientUpload::class, 'getPrescriptionMetadata'])->name('prescription.file.metadata');
});

Route::prefix('dashboard/suppliers')->middleware(['auth', 'admin'])->name('suppliers.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/create', [SupplierController::class, 'create'])->name('create');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [SupplierController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SupplierController::class, 'update'])->name('update');
    Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('destroy');
});

Route::post('/prescriptions/{id}/items', [PrescriptionItemController::class, 'saveItems'])->middleware(['auth', 'admin'])->name('prescription.items.save');
Route::get('/prescriptions/{id}/items', [PrescriptionItemController::class, 'getItems'])->middleware(['auth', 'admin']);

Route::get('/POS', fn() => redirect()->route('POS'))->name('POS.index');

Route::get('/login', [AuthController::class, 'showLoginForm1'])->name('login.form');
Route::post('/login', [AuthController::class, 'login1'])->name('customer.login');
Route::get('/signup/step1', [AuthController::class, 'showSignupStepOne'])->name('signup.step_one');
Route::post('/signup/step1', [AuthController::class, 'handleSignupStepOne'])->name('signup.step_one.submit');
Route::get('/signup/step2', [AuthController::class, 'showSignupStepTwo'])->name('signup.step_two');
Route::post('/signup/step2', [AuthController::class, 'handleSignupStepTwo'])->name('signup.step_two.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('customer.logout');

Route::get('/home', [AuthController::class, 'Home'])->name('home')->middleware('auth:customer');
Route::get('/home/products', [ProductController::class, 'customerIndex'])->name('customer.products');
Route::get('/home/contact-us', fn() => view('client.contact-us'))->name('contact-us');
Route::get('/profile', [AuthController::class, 'show'])->name('customer.profile');

Route::middleware(['auth:customer'])->group(function () {
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

Route::get('/feedback', function () {
    return view('client.feedback');
});

Route::get('/reset-password', fn() => view('reset-password'))->name('password.reset');

Route::post('/send-code', function (Request $request) {
    $request->validate(['email' => 'required|email']);
    $code = rand(1000, 9999);

    Session::put('reset_code', $code);
    Session::put('reset_email', $request->input('email'));

    return back()->with('status', 'We sent a 4-digit code: ' . $code);
})->name('password.send-code');

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
