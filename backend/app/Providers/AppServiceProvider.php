<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Loan;
use Illuminate\Support\Facades\View;

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
        View::composer('layouts.admin', function ($view) {
            $view->with('pendingCount', Loan::whereIn('status', [
                Loan::STATUS_DIAJUKAN,
                Loan::STATUS_PENGEMBALIAN_DIAJUKAN,
            ])->count());
        });
    }
}
