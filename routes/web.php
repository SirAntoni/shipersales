<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\OnDemandProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Middleware\RedirectIfNoDashboardPermission;
use App\Services\PendingDocumentsService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CanceledController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DocumentController;

Route::middleware(['guest'])->group(function () {
    Route::get("/login",[AuthController::class,'showLoginForm'])->name('login');
    Route::get("/ml",function(){
        return response()->json(['message' => 'view ml']);
    })->name('ml');
});

Route::get('/cron/sunat/resend-pending', function (PendingDocumentsService $service) {
    $service->resendAll();

    return response()->json([
        'status'  => 'ok',
        'message' => 'Reenvío de documentos pendientes ejecutado.',
    ]);
})->name('sunat.resend.pending');

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class,'index'])->middleware(['auth', RedirectIfNoDashboardPermission::class])->name('dashboard.index');
    Route::get('/dashboard/inventory', [DashboardController::class,'inventory'])->name('dashboard.inventory');
    Route::get('/inventory', [InventoryController::class,'index'])->name('inventory');

    Route::resource('categories', CategoryController::class)->middleware('can:store','can:categories');
    Route::resource('brands', BrandController::class)->middleware('can:store','can:brands');
    Route::resource('articles', ArticleController::class)->middleware('can:store','can:articles');
    Route::resource('on-demand-products', OnDemandProductController::class)->middleware('can:store','can:articles');
    Route::resource('providers', ProviderController::class)->middleware('can:purchases','can:providers');
    Route::resource('clients', ClientController::class)->middleware('can:sales','can:clients');
    Route::resource('users', UserController::class)->middleware('can:users');
    Route::resource('vouchers', VoucherController::class)->middleware('can:settings');
    Route::get('settings', [SettingController::class,'index'])->name('settings')->middleware('can:settings');
    Route::resource('contacts', ContactController::class)->middleware('can:settings');
    Route::resource('payment-methods', PaymentMethodController::class)->middleware('can:settings');
    Route::resource('purchases', PurchaseController::class)->middleware('can:purchases.index');
    Route::get('usa-purchases', [PurchaseController::class, 'usa'])->name('usa_purchases')->middleware('can:purchases');
    Route::get('canceled_purchases', [CanceledController::class,'purchases'])->name('canceled_purchases')->middleware('can:purchases','can:canceled_purchases');
    Route::resource('sales', SaleController::class)->middleware('can:sales');
    Route::get('commissions',[SaleController::class,'commissions'])->name('commissions')->middleware('can:commissions');
    Route::resource('documents', DocumentController::class)->middleware('can:documents');
    Route::get('canceled', [CanceledController::class,'index'])->name('canceled')->middleware('can:canceled');
    Route::get('returns', [SaleController::class,'returns'])->name('returns')->middleware('can:sales');
    Route::get('reports', [ReportController::class,'index'])->name('reports')->middleware('can:reports');
    Route::get('reports/dayli/export', [ReportController::class, 'dayli'])->name('reports.dayli.export')->middleware('can:reports');
    Route::get('reports/custom/export', [ReportController::class, 'custom'])->name('reports.custom.export')->middleware('can:reports');
    Route::get('reports/month/export', [ReportController::class, 'month'])->name('reports.month.export')->middleware('can:reports');
    Route::get('reports/articles/export', [ReportController::class, 'articles'])->name('reports.articles')->middleware('can:store');
    Route::get('reports/on-demand-products/export', [ReportController::class, 'onDemandProducts'])->name('reports.on-demand-products')->middleware('can:store');
    Route::get('reports/commissions/export', [ReportController::class, 'commissions'])->name('reports.commissions.export')->middleware('can:commissions');
    Route::get('kardex', [KardexController::class,'index'])->name('kardex')->middleware('can:kardex');
    Route::get('pdf/{id}', [SaleController::class,'pdf'])->name('pdf.view');
    Route::post("/logout",[AuthController::class,'logout'])->name('logout');
    Route::get('documents/{path}/download', [DocumentController::class, 'download'])->where('path', '.*')->name('documents.download');
    Route::get('documents/{id}/credit-note', [DocumentController::class, 'creditNote'])->name('documents.credit-note.blade.php')->middleware('can:documents');
    Route::get('documents/{document}/retry', [DocumentController::class, 'retry'])->name('documents.retry')->middleware('can:documents');
    //Start Reset Cache en Cpanel
    Route::get('/clear-all-caches', function () {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('permission:cache-reset');
        return "Todos los caches (config, app, rutas y permisos) han sido limpiados.";
    });
//    Route::get('/clear-permission-cache', function () {
//        Cache::forget('spatie.permission.cache');
//        return "Cache de permisos borrado.";
//    });
    //End Reset Cache en Cpanel

    //Start create password
//    Route::get('/create-password/{password}', function ($password) {
//        $new_passowrd = bcrypt($password);
//        return $new_passowrd;
//    });
    //End create password

    Route::get('/run-migrations', function () {

        Artisan::call('migrate', ['--force' => true]);

        return response( nl2br(Artisan::output()), 200 )
            ->header('Content-Type', 'text/html');
    });

});


Route::get('theme-switcher', function () {
    return "Hola Mundo";
})->name('theme-switcher');


