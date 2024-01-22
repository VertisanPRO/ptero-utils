<?php

namespace Wemx\Utils\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Progress;
use function Laravel\Prompts\{info, progress, spin};

class BuildCommand extends Command
{
    protected $description = 'Install NodeJS, Yarn and Build assets';

    protected $signature = 'utils:build {--progress}';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $progress = progress(label: 'Installing NodeJS, Yarn and Building assets', steps: 3);
        $progress->start();
        $progress->advance();

        spin(
            fn() => exec(($this->isRHEL() ? 'yum' : 'apt-get') . ' remove -y -q cmdtest'),
            'Removing cmdtest'
        );

        $progress->advance();

        spin(
            fn() => $this->configureNodeJS(),
            'Installing and configuring NodeJS'
        );

        $progress->advance();

        spin(
            function () {
                $code = null;
                exec('yarn -v 2>/dev/null', $output, $code);

                if ($code !== 0)
                    exec('npm install -g yarn');

                exec('yarn --silent');
            },
            'Installing Yarn'
        );

        $progress->finish();

        info('Building assets (this may take a while)');
        $this->option('progress')
            ? exec('yarn build:production --progress')
            : exec('yarn build:production');
    }

    private function configureNodeJS(): void
    {
        $output = null;
        $code = null;
        exec('node -v 2>/dev/null', $output, $code);

        ($code === 0)
            ? (version_compare(trim($output[0]), 'v17', '>') && putenv('NODE_OPTIONS=--openssl-legacy-provider'))
            : $this->installNodeJS();
    }

    private function installNodeJS(): void
    {
        if ($this->isRHEL()) {
            exec('yum install -y -q https://rpm.nodesource.com/pub_16.x/nodistro/repo/nodesource-release-nodistro-1.noarch.rpm');
            exec('yum install -y -q nodejs');
        } else {
            exec('mkdir -p /etc/apt/keyrings');
            exec('curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor --yes -o /etc/apt/keyrings/nodesource.gpg');
            exec('echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_16.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list > /dev/null');
            exec('apt-get update -qqq');
            exec('apt-get install -y -qqq nodejs');
        }
    }

    private function isRHEL(): bool
    {
        return file_exists('/etc/redhat-release');
    }
}
