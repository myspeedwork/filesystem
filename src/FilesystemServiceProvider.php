<?php

/*
 * This file is part of the Speedwork package.
 *
 * (c) Sankar <sankar.suda@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

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

        $app['finder'] = $app->factory(function () {
            return Finder::create();
        });

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
