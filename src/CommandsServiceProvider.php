<?php

namespace Wemx\Utils;

use Illuminate\Support\ServiceProvider;
use Wemx\Utils\Commands\InstallCommand;

class CommandsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            InstallCommand::class,
        ]);
    }
}