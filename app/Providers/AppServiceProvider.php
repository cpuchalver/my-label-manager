<?php

namespace App\Providers;

use App\Models\Label;
use App\Models\User;
use Filament\Events\TenantSet;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Event;
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
        DevCommands::artisan('reverb:start', 'reverb');

        Event::listen(function (TenantSet $event): void {
            $label = $event->getTenant();
            $user = $event->getUser();

            if (! $label instanceof Label) {
                return;
            }

            if (! $user instanceof User) {
                return;
            }

            $user->rememberLatestLabel($label);
        });
    }
}
