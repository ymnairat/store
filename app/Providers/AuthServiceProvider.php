<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // Make Blade @can check your hasPermission method
        Gate::before(function ($user, $ability) {
            // $ability will be 'products.view' etc.
            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true; // grant access
            }
            // return null to let other gates/policies decide
        });
    }
}
