<?php

use App\Http\Controllers\Admin\BusinessSettingsController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\CashierPasswordController;
use App\Http\Controllers\Admin\CashierStatusController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CategoryStatusController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStatusController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SaleController as AdminSaleController;
use App\Http\Controllers\Admin\SaleReversalController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockBatchController;
use App\Http\Controllers\Admin\StockLossController;
use App\Http\Controllers\Admin\StockLossReversalController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\StockReceiptController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\InitialAdministratorController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Cashier\DashboardController as CashierDashboardController;
use App\Http\Controllers\Cashier\PosController;
use App\Http\Controllers\Cashier\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/media/business-logo', [MediaController::class, 'businessLogo'])->name('media.business-logo');

Route::middleware('guest')->group(function (): void {
    Route::get('/setup', [InitialAdministratorController::class, 'create'])->name('setup.create');
    Route::post('/setup', [InitialAdministratorController::class, 'store'])->middleware('throttle:6,1')->name('setup.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/media/products/{product}/image', [MediaController::class, 'productImage'])->name('media.products.image');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/profile/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('password.update');

    Route::prefix('admin')->name('admin.')->middleware('role:administrator')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/pos', PosController::class)->name('pos');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('/reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/settings/business', [BusinessSettingsController::class, 'edit'])->name('business-settings.edit');
        Route::put('/settings/business', [BusinessSettingsController::class, 'update'])->name('business-settings.update');
        Route::patch('/categories/{category}/status', CategoryStatusController::class)->name('categories.status.update');
        Route::resource('categories', CategoryController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/products/{product}/status', ProductStatusController::class)->name('products.status.update');
        Route::resource('products', ProductController::class)->except('destroy');
        Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('expenses', ExpenseController::class)->except('destroy');
        Route::get('/sales', [AdminSaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [AdminSaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/receipt', [AdminSaleController::class, 'receipt'])->name('sales.receipt');
        Route::get('/sales/{sale}/reversal', [SaleReversalController::class, 'create'])->name('sales.reversal.create');
        Route::post('/sales/{sale}/reversal', [SaleReversalController::class, 'store'])->name('sales.reversal.store');
        Route::get('/inventory', InventoryController::class)->name('inventory.index');
        Route::get('/inventory/movements', StockMovementController::class)->name('inventory.movements.index');
        Route::get('/inventory/batches/{batch}', StockBatchController::class)->name('inventory.batches.show');
        Route::get('/inventory/losses/{loss}/reversal', [StockLossReversalController::class, 'create'])->name('inventory.losses.reversal.create');
        Route::post('/inventory/losses/{loss}/reversal', [StockLossReversalController::class, 'store'])->name('inventory.losses.reversal.store');
        Route::resource('/inventory/losses', StockLossController::class)
            ->names('inventory.losses')
            ->only(['index', 'create', 'store', 'show']);
        Route::resource('/inventory/receipts', StockReceiptController::class)
            ->names('inventory.receipts')
            ->only(['index', 'create', 'store', 'show']);
        Route::resource('/inventory/adjustments', StockAdjustmentController::class)
            ->names('inventory.adjustments')
            ->only(['index', 'create', 'store', 'show']);
        Route::get('/staff/{staff}/password', [CashierPasswordController::class, 'edit'])->name('staff.password.edit');
        Route::put('/staff/{staff}/password', [CashierPasswordController::class, 'update'])->name('staff.password.update');
        Route::patch('/staff/{staff}/status', CashierStatusController::class)->name('staff.status.update');
        Route::resource('staff', CashierController::class)->except('destroy');
    });

    Route::prefix('cashier')->name('cashier.')->middleware('role:cashier')->group(function (): void {
        Route::get('/dashboard', CashierDashboardController::class)->name('dashboard');
        Route::get('/pos', PosController::class)->name('pos');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    });
});
