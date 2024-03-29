<?php

namespace Wemx\Utils\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\{progress, text, select, confirm, info, warning, spin};

class UpdateCommand extends Command
{
    protected $description = 'Update the Panel';

    protected $signature = 'utils:update {--force} {--utils-version=}';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $userDetails = posix_getpwuid(fileowner('public'));
        $user = $userDetails['name'] ?? 'www-data';

        $groupDetails = posix_getgrgid(filegroup('public'));
        $group = $groupDetails['name'] ?? 'www-data';

        $remove = true;

        if (!$this->option('force')) {
            $confirm = confirm(
                label: "Your webserver user has been detected as <fg=green>[{$user}]</>: is this correct?",
                default: true,
            );

            if (!$confirm) {
                $user = select(
                    label: 'Please enter the name of the user running your webserver process. This varies from system to system, but is generally "www-data", "nginx", or "apache".',
                    options: [
                        'www-data' => 'www-data',
                        'nginx' => 'nginx',
                        'apache' => 'apache',
                        'own' => 'Your own user (type after you choose this)'
                    ],
                    default: 'www-data'
                );

                if ($user === 'own')
                    $user = text('Please enter the name of the user running your webserver process');
            }

            $confirm = confirm(
                label: "Your webserver group has been detected as <fg=green>[{$group}]</>: is this correct?",
                default: true,
            );

            if (!$confirm) {
                $group = select(
                    label: 'Please enter the name of the group running your webserver process. Normally this is the same as your user.',
                    options: [
                        'www-data' => 'www-data',
                        'nginx' => 'nginx',
                        'apache' => 'apache',
                        'own' => 'Your own group (type after you choose this)'
                    ],
                    default: 'www-data'
                );

                if ($group === 'own')
                    $group = text('Please enter the name of the group running your webserver process');
            }

            $remove = confirm(
                label: 'Would you like to remove the <fg=red>resources/scripts</> folder? It is useful for removing all third-party changes.',
                default: false,
            );

            $confirm = confirm(
                label: 'You are about to update the Panel. This process will removes all third-party changes and updates Pterodactyl to the latest available version, do you wish to continue?',
                default: false,
            );

            if (!$confirm) {
                warning('Updating has been cancelled');
                return;
            }
        }

        if ($remove)
            exec('rm -rf resources/scripts');

        $progress = progress(label: 'Updating the Panel', steps: 7);
        $progress->start();

        spin(
            fn() => exec('curl -s -L https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz | tar -xzv'),
            'Downloading latest stable version for Pterodactyl'
        );

        usleep(800);
        $progress->advance();

        spin(
            function () {
                exec('chmod -R 755 storage/* bootstrap/cache');
                sleep(1);
            },
            'Setting correct permissions'
        );

        $progress->advance();

        spin(
            fn() => exec('php artisan view:clear && php artisan config:clear'),
            'Clearing cache'
        );

        usleep(800);
        $progress->advance();

        spin(
            fn() => exec('composer install --no-dev --optimize-autoloader -n -q'),
            'Installing composer dependencies'
        );

        $progress->advance();

        $version = $this->option('utils-version');
        spin(
            fn() => exec('composer require -n -q wemx/utils' . ($version ? " {$version}" : '')),
            'Installing <fg=green>wemx/utils</> back'
        );

        $progress->advance();

        spin(
            fn() => exec('php artisan migrate --force'),
            'Migrating the database'
        );

        $progress->advance();

        $basePath = base_path();
        spin(
            fn() => exec("chown -R {$user}:{$group} {$basePath}/*"),
            'Setting correct permissions'
        );

        $progress->advance();

        spin(
            fn() => exec('php artisan queue:restart'),
            'Restarting queue worker'
        );

        $progress->finish();

        exec('php artisan up');

        info('Pterodactyl has been reverted to default and updated to the latest version');

        if (!$this->option('force')) {
            $confirm = confirm(
                label: 'Would you like to keep <fg=green>wemx/utils</>?',
                default: true,
            );

            if (!$confirm) {
                spin(
                    fn() => exec('composer remove -n -q wemx/utils'),
                    'Removing <fg=green>wemx/utils</>'
                );
                $this->line('<fg=green>wemx/utils</> was successfully removed');
            }
        }

        return;
    }
}
