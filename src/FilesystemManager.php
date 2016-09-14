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

use Aws\S3\S3Client;
use Closure;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter as S3Adapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemInterface as FlyFilesystemInterface;
use League\Flysystem\Rackspace\RackspaceAdapter;
use OpenCloud\Rackspace;
use Speedwork\Util\Arr;

class FilesystemManager
{
    /**
     * The application instance.
     *
     * @var \Speedwork\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved filesystem drivers.
     *
     * @var array
     */
    protected $disks = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new filesystem manager instance.
     *
     * @param \Speedwork\Core\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a filesystem instance.
     *
     * @param string $name
     *
     * @return Filesystem
     */
    public function drive($name = null)
    {
        return $this->disk($name);
    }

    /**
     * Get a filesystem instance.
     *
     * @param string $name
     *
     * @return Filesystem
     */
    public function disk($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param string $name
     *
     * @return Filesystem
     */
    protected function get($name)
    {
        return isset($this->disks[$name]) ? $this->disks[$name] : $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param string $name
     *
     * @return Filesystem
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        return $this->{'create'.ucfirst($config['driver']).'Driver'}($config);
    }

    /**
     * Call a custom driver creator.
     *
     * @param array $config
     *
     * @return Filesystem
     */
    protected function callCustomCreator(array $config)
    {
        $driver = $this->customCreators[$config['driver']]($this->app, $config);

        if ($driver instanceof FlyFilesystemInterface) {
            return $this->adapt($driver);
        }

        return $driver;
    }

    /**
     * Create an instance of the local driver.
     *
     * @param array $config
     *
     * @return Filesystem
     */
    public function createLocalDriver(array $config)
    {
        return $this->adapt(new Flysystem(new LocalAdapter($config['root'])));
    }

    /**
     * Create an instance of the ftp driver.
     *
     * @param array $config
     *
     * @return Filesystem
     */
    public function createFtpDriver(array $config)
    {
        $ftpConfig = Arr::only($config, [
            'host', 'username', 'password', 'port', 'root', 'passive', 'ssl', 'timeout',
        ]);

        return $this->adapt(new Flysystem(new FtpAdapter($ftpConfig)));
    }

    /**
     * Create an instance of the Amazon S3 driver.
     *
     * @param array $config
     *
     * @return \Speedwork\Filesystem\Cloud
     */
    public function createS3Driver(array $config)
    {
        $config = $this->formatS3Config($config);

        $root = isset($config['root']) ? $config['root'] : null;

        return $this->adapt(
            new Flysystem(new S3Adapter(new S3Client($config), $config['bucket'], $root))
        );
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * @param array $config
     *
     * @return array
     */
    protected function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return $config;
    }

    /**
     * Create an instance of the Rackspace driver.
     *
     * @param array $config
     *
     * @return \Speedwork\Filesystem\Cloud
     */
    public function createRackspaceDriver(array $config)
    {
        $client = new Rackspace($config['endpoint'], [
            'username' => $config['username'], 'apiKey' => $config['key'],
        ]);

        return $this->adapt(new Flysystem(
            new RackspaceAdapter($this->getRackspaceContainer($client, $config))
        ));
    }

    /**
     * Get the Rackspace Cloud Files container.
     *
     * @param \OpenCloud\Rackspace $client
     * @param array                $config
     *
     * @return \OpenCloud\ObjectStore\Resource\Container
     */
    protected function getRackspaceContainer(Rackspace $client, array $config)
    {
        $urlType = Arr::get($config, 'url_type');

        $store = $client->objectStoreService('cloudFiles', $config['region'], $urlType);

        return $store->getContainer($config['container']);
    }

    /**
     * Adapt the filesystem implementation.
     *
     * @param \League\Flysystem\FlyFilesystemInterface $filesystem
     *
     * @return Filesystem
     */
    protected function adapt(FlyFilesystemInterface $filesystem)
    {
        return new FilesystemAdapter($filesystem);
    }

    /**
     * Get the filesystem connection configuration.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["filesystems.disks.{$name}"];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['filesystems.default'];
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string   $driver
     * @param \Closure $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->disk(), $method], $parameters);
    }
}
