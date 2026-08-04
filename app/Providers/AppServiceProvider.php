<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\ShippingRateProvider;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\MeiliSearchService;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\ManualPaymentGateway;
use App\Services\Payment\MidtransSnapGateway;
use App\Services\Shipping\FakeCourierRateProvider;
use App\Services\Shipping\StaticCityRateProvider;
use App\Support\ShopSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MeiliSearchService::class, function () {
            return new MeiliSearchService;
        });

        $this->app->singleton(StaticCityRateProvider::class);

        $this->app->bind(ShippingRateProvider::class, function ($app) {
            return match (config('shop.shipping_driver', 'static')) {
                'fake' => $app->make(FakeCourierRateProvider::class),
                'static' => $app->make(StaticCityRateProvider::class),
                default => throw new InvalidArgumentException('SHIPPING_DRIVER tidak dikenali.'),
            };
        });

        $this->app->bind(PaymentGateway::class, function ($app) {
            return match (ShopSettings::paymentDriver()) {
                'fake' => $app->make(FakePaymentGateway::class),
                'midtrans' => $app->make(MidtransSnapGateway::class),
                'manual' => $app->make(ManualPaymentGateway::class),
                default => throw new InvalidArgumentException('PAYMENT_DRIVER tidak dikenali.'),
            };
        });
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);

        $this->configureDefaults();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
