<?php

namespace Speedwork\Filesystem;

use Speedwork\Container\Container;
use Speedwork\Container\ServiceProvider;
use Symfony\Component\Finder\Finder;

/**
 * Symfony Filesystem & Finder component Provider.
 *
 * @author sankar <sankar.suda@gmail.com>
 */
class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['fs'] = function ($app) {
            return new Filesystem();
        };

        $app['files'] = function ($app) {
            return $app['fs'];
        };

        $app['finder'] = function ($app) {
            return new Finder();
        };

        $app['filesystem'] = function ($app) {
            return new FilesystemManager($app);
        };

        $app['filesystem.disk'] = function ($app) {
            $config = ($app['filesystems.default'])
            ? $app['filesystems.default']
            : $app['config']->get('filesystems.default');

            return $app['filesystem']->disk($config);
        };

        $app['filesystem.cloud'] = function ($app) {
            $config = ($app['filesystems.cloud'])
            ? $app['filesystems.cloud']
            : $app['config']->get('filesystems.cloud');

            return $app['filesystem']->disk($config);
        };
    }
}
