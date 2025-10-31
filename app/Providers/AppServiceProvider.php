<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\NavigationController;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Compose on every view render — ensures auth()->id() is available
        View::composer('*', function ($view) {
            $menu = [];
            if (Auth::check()) {
                $menu = app(NavigationController::class)->buildMenuForUser(Auth::id());
            }
            // Share EXACT menu for the current user
            $view->with('menuItems', $menu);
        });
    }
}
