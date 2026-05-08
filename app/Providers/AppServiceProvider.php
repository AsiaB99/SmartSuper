<?php

namespace App\Providers;

use App\Models\Despensa;
use App\Models\Lista;
use App\Policies\DespensaPolicy;
use App\Policies\ListaPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        App::setLocale('es');

        Gate::policy(Lista::class, ListaPolicy::class);
        Gate::policy(Despensa::class, DespensaPolicy::class);
    }
}
