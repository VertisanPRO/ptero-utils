<?php

namespace Wemx\Quantum\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\{progress, spin};

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
            fn() => exec(($this->isRHEL() ? 'yum' : 'apt') . ' remove -y -q cmdtest'),
            'Removing cmdtest'
        );

        $progress->advance();

        spin(
            fn() => $this->configureNodeJS(),
            'Installing and configuring NodeJS'
        );

        $progress->advance();

        spin(
            fn() => $this->buildAssets(),
            'Building assets (this may take a while)'
        );

        $progress->finish();
    }

    private function configureNodeJS(): void
    {
        $output = null;
        $code = null;
        exec('node -v 2>/dev/null', $output, $code);

        ($code === 0 && version_compare(trim($output[0]), 'v17', '>'))
            ? putenv('NODE_OPTIONS=--openssl-legacy-provider')
            : $this->installNodeJS();
    }

    private function installNodeJS(): void
    {
        if ($this->isRHEL()) {
            system('yum install -y -q https://rpm.nodesource.com/pub_16.x/nodistro/repo/nodesource-release-nodistro-1.noarch.rpm');
            system('yum install -y -q nodejs');
        } else {
            exec('mkdir -p /etc/apt/keyrings');
            exec('curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor --yes -o /etc/apt/keyrings/nodesource.gpg');
            exec('echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_16.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list > /dev/null');
            system('apt update -q');
            system('apt install -y -q nodejs');
        }
    }

    private function buildAssets(): void
    {
        $code = null;
        exec('yarn -v 2>/dev/null', $output, $code);

        if ($code !== 0)
            exec('npm install -g yarn');

        exec('yarn');
        $this->option('progress')
            ? system('yarn build:production --progress')
            : system('yarn build:production');
    }

    private function isRHEL(): bool
    {
        return file_exists('/etc/redhat-release');
    }
}
