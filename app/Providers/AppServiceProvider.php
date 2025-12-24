<?php

namespace App\Providers;

use Barryvdh\DomPDF\ServiceProvider as DomPDFServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Daftarkan DomPDF ServiceProvider jika auto-discovery tidak berfungsi
        $this->app->register(DomPDFServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

