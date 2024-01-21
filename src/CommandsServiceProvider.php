<?php

namespace Wemx\Utils;

use Illuminate\Support\ServiceProvider;
use Wemx\Utils\Commands\UtilsBuildCommand;

class CommandsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            UtilsBuildCommand::class,
        ]);
    }
}