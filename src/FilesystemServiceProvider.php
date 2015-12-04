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
class FilesytemServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['fs'] = function ($app) {
            return new Filesystem();
        };

        $app['finder'] = function ($app) {
            return new Finder();
        };

        $app['filesystem'] = function ($app) {
            return new FilesystemManager($app);
        };

        $app['filesystem.disk'] = function ($app) {
            return $app['filesystem']->disk($app['filesystems.default']);
        };

        $app['filesystem.cloud'] = function ($app) {
            return $app['filesystem']->disk($app['filesystems.cloud']);
        };
    }
}
