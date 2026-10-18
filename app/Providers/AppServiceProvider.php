<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\ArticleContactPrice;
use App\Models\ArticleMarketplace;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\InventoryAdjustment;
use App\Models\InventoryCount;
use App\Models\InventorySession;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Setting;
use App\Models\UsaPurchase;
use App\Models\User;
use App\Models\Voucher;
use App\Observers\ArticleObserver;
use App\Observers\AuditObserver;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;  // ← importa Route

class AppServiceProvider extends ServiceProvider
{
    /**
     * Modelos auditados en audit_logs. Quedan fuera los catalogos de ubigeo
     * (departamentos, provincias, distritos), que no se editan.
     */
    private const AUDITADOS = [
        Article::class, ArticleContactPrice::class, ArticleMarketplace::class,
        Brand::class, Category::class, Client::class, Contact::class,
        Document::class, DocumentDetail::class,
        InventoryAdjustment::class, InventoryCount::class, InventorySession::class,
        PaymentMethod::class, Provider::class,
        Purchase::class, PurchaseDetail::class,
        Quotation::class, QuotationDetail::class,
        Sale::class, SaleDetail::class,
        Setting::class, UsaPurchase::class, User::class, Voucher::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Article::observe(ArticleObserver::class);

        foreach (self::AUDITADOS as $modelo) {
            $modelo::observe(AuditObserver::class);
        }

        // Inicios y cierres de sesion. Nunca se guarda la contraseña intentada.
        Event::listen(Login::class, fn (Login $e) => AuditLogger::registrar('login', 'users', $e->user->getAuthIdentifier(), null, [
            'email'      => $e->user->email ?? null,
            'remember'   => $e->remember,
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
        ], $e->user->getAuthIdentifier()));

        Event::listen(Logout::class, fn (Logout $e) => $e->user && AuditLogger::registrar('logout', 'users', $e->user->getAuthIdentifier(), null, null, $e->user->getAuthIdentifier()));

        Event::listen(Failed::class, fn (Failed $e) => AuditLogger::registrar('login_failed', 'users', $e->user?->getAuthIdentifier(), null, [
            'email'      => $e->credentials['email'] ?? null,
            'motivo'     => $e->user ? 'contraseña incorrecta' : 'email no registrado',
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
        ], $e->user?->getAuthIdentifier()));
    }
}
