<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use LogicException;

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
        if (! $this->app->environment('production') && getenv('VERCEL') !== '1') {
            return;
        }

        if (! in_array(config('session.driver'), ['cookie', 'database'], true)) {
            throw new LogicException('Production SESSION_DRIVER must be set to cookie or database.');
        }

        $connectionName = config('database.default');
        $supportedConnections = ['mysql', 'mariadb', 'pgsql', 'sqlsrv'];

        if (! in_array($connectionName, $supportedConnections, true)) {
            throw new LogicException('Production DB_CONNECTION must use an external database driver.');
        }

        $connection = config("database.connections.{$connectionName}", []);

        if (! empty($connection['url'])) {
            throw new LogicException('Production database configuration must use the individual DB_* environment variables, not DB_URL.');
        }

        $missingSettings = [];

        foreach (['host', 'database', 'username', 'password'] as $setting) {
            if (! is_string($connection[$setting] ?? null) || trim($connection[$setting]) === '') {
                $missingSettings[] = 'DB_'.strtoupper($setting);
            }
        }

        if (in_array(strtolower((string) ($connection['host'] ?? '')), ['localhost', '127.0.0.1', '::1'], true)) {
            $missingSettings[] = 'DB_HOST (must be external)';
        }

        if ($missingSettings !== []) {
            throw new LogicException('Production database configuration is incomplete: '.implode(', ', $missingSettings).'.');
        }
    }
}
