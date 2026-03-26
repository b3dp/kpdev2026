<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
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
        if (config('scout.driver') === 'tntsearch') {
            File::ensureDirectoryExists(config('scout.tntsearch.storage'));
        }

        if (! app()->runningUnitTests()) {
            Event::listen(CommandStarting::class, function (CommandStarting $event): void {
                $destructiveCommands = [
                    'migrate:fresh',
                    'migrate:refresh',
                    'migrate:reset',
                    'db:wipe',
                ];

                $isDestructive = in_array($event->command, $destructiveCommands, true);

                if ($isDestructive && ! (bool) env('DESTRUCTIVE_DB_COMMANDS_ALLOWED', false)) {
                    throw new \RuntimeException('Yikici veritabani komutlari devre disi. Gecici izin icin DESTRUCTIVE_DB_COMMANDS_ALLOWED=true ayarlayin.');
                }
            });
        }
    }
}
