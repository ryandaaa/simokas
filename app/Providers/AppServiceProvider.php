<?php

namespace App\Providers;

use App\Support\Broadcasting\PusherHttpBroadcaster;
use Illuminate\Support\Facades\Broadcast;
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
        // Driver broadcast Pusher tanpa SDK (lihat PusherHttpBroadcaster).
        // Menggantikan driver `pusher` bawaan agar tidak butuh guzzle 7.
        Broadcast::extend('pusher', function ($app, array $config): PusherHttpBroadcaster {
            $options = $config['options'] ?? [];

            return new PusherHttpBroadcaster(
                appId: (string) ($config['app_id'] ?? ''),
                key: (string) ($config['key'] ?? ''),
                secret: (string) ($config['secret'] ?? ''),
                cluster: (string) ($options['cluster'] ?? 'ap1'),
                host: isset($options['host']) && $options['host'] !== '' ? (string) $options['host'] : null,
                port: (int) ($options['port'] ?? 443),
                scheme: (string) ($options['scheme'] ?? 'https'),
            );
        });
    }
}
