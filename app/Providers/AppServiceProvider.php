<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Directiva Blade personalizada @modulo('celador_nocturno') ... @endmodulo
        Blade::if('modulo', function (string $modulo): bool {
            $user = auth()->user();
            if (! $user) {
                return false;
            }

            $finca = $user->finca_segura;

            return $finca ? $finca->tieneModulo($modulo) : true;
        });
    }
}
