<?php
namespace GGuney\RMigration;

use Illuminate\Support\ServiceProvider;

class RMigrationServiceProvider extends ServiceProvider
{
    protected $commands = [
        'GGuney\RMigration\Commands\MakeReverseMigration'
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }

    public function boot()
    {

    }

}
