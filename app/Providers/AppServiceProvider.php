<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\NavigationController;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share navigation menu with all views
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $navController = new NavigationController();
                $navController->loadMenu();
            } else {
                View::share('menuItems', []);
            }
        });
    }
}