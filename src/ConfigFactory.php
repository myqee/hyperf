<?php

declare(strict_types = 1);

namespace MyQEE\Hyperf;

use Dotenv\Dotenv;
use Hyperf\Config\ProviderConfig;
use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;

class ConfigFactory {
    public function __invoke(ContainerInterface $container) {
        // Load env before config.
        if (file_exists(BASE_PATH . '/.env')) {
            Dotenv::create([BASE_PATH])->load();
        }

        $configPath     = BASE_PATH . '/config/';
        $config         = $this->readConfig($configPath . 'config.php');
        $autoloadConfig = $this->readPaths([$configPath . 'autoload']);
        $merged         = array_merge_recursive(ProviderConfig::load(), $config, ...$autoloadConfig);

        return new Config($merged);
    }

    private function readConfig(string $configPath): array {
        $config = [];
        if (file_exists($configPath) && is_readable($configPath)) {
            $config = require $configPath;
        }
        return is_array($config) ? $config : [];
    }

    private function readPaths(array $paths) {
        $configs = [];
        $finder  = new Finder();
        $finder->files()->in($paths)->name('*.php');
        foreach ($finder as $file) {
            $configs[] = [
                $file->getBasename('.php') => require $file->getRealPath(),
            ];
        }
        return $configs;
    }
}
