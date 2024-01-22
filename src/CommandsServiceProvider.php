<?php

namespace Wemx\Utils;

use Illuminate\Support\ServiceProvider;
use Wemx\Utils\Commands\BuildCommand;
use Wemx\Utils\Commands\UpdateCommand;

class CommandsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            BuildCommand::class,
            UpdateCommand::class,
        ]);
    }
}
