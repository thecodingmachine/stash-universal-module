<?php

namespace TheCodingMachine;

use Psr\Container\ContainerInterface;
use Interop\Container\Factories\Alias;
use Interop\Container\Factories\Parameter;
use Interop\Container\ServiceProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Stash\Driver\Apc;
use Stash\Driver\BlackHole;
use Stash\Driver\Composite;
use Stash\Driver\Ephemeral;
use Stash\Driver\FileSystem;
use Stash\Driver\Memcache;
use Stash\Driver\Redis;
use Stash\Driver\Sqlite;
use Stash\DriverList;
use Stash\Interfaces\DriverInterface;
use Stash\Interfaces\PoolInterface;
use Stash\Pool;
use Twig_Environment;
use Twig_LoaderInterface;
use Twig_Loader_Chain;
use Twig_Loader_Filesystem;

class StashServiceProvider implements ServiceProviderInterface
{
    const PACKAGE = 'thecodingmachine.stash-universal-module';

    private $suffix;

    /**
     * @param string $suffix The Suffix added to all created instances (if you need more than one cache).
     */
    public function __construct(string $suffix = null)
    {
        $this->suffix = $suffix ? '.'.$suffix : '';
    }

    public function getFactories()
    {
        return [
            'stash'.$this->suffix.'.composite.options' => function(ContainerInterface $container) : array
            {
                // Let's put the Ephemeral driver first.
                $drivers = [
                    self::get($container, Ephemeral::class.$this->suffix)
                ];

                // Now, let's put APC if available.
                if (Apc::isAvailable()) {
                    $drivers[] = self::get($container, Apc::class.$this->suffix);
                } else {
                    // Else, let's enable teh filesystem by default.
                    // TODO: look in options and enable the configured options!
                    $drivers[] = self::get($container, FileSystem::class.$this->suffix);
                }

                return [
                    'drivers' => $drivers
                ];
            },
            Composite::class.$this->suffix => function(ContainerInterface $container) : Composite
            {
                return new Composite(self::get($container, 'stash'.$this->suffix.'.composite.options'));
            },
            Ephemeral::class.$this->suffix => [self::class,'createEphemeralDriver'],
            BlackHole::class.$this->suffix => [self::class,'createBlackHoleDriver'],

            Apc::class.$this->suffix => function(ContainerInterface $container) : Apc
            {
                return new Apc(self::get($container, 'stash'.$this->suffix.'.apc.options', []));
            },

            FileSystem::class.$this->suffix => function(ContainerInterface $container) : FileSystem
            {
                return new FileSystem(self::get($container, 'stash'.$this->suffix.'.filesystem.options', []));
            },

            Memcache::class.$this->suffix => function(ContainerInterface $container) : Memcache
            {
                return new Memcache(self::get($container, 'stash'.$this->suffix.'.memcache.options', []));
            },

            Redis::class.$this->suffix => function(ContainerInterface $container) : Redis
            {
                return new Redis(self::get($container, 'stash'.$this->suffix.'.redis.options', []));
            },

            Sqlite::class.$this->suffix => function(ContainerInterface $container) : Sqlite
            {
                return new Sqlite(self::get($container, 'stash'.$this->suffix.'.sqlite.options', []));
            },

            DriverInterface::class.$this->suffix => new Alias(Composite::class.$this->suffix),

            Pool::class.$this->suffix => function(ContainerInterface $container) : Pool
            {
                return new Pool(self::get($container, DriverInterface::class.$this->suffix, []));
            },

            PoolInterface::class.$this->suffix => new Alias(Pool::class.$this->suffix),
            CacheItemPoolInterface::class.$this->suffix => new Alias(PoolInterface::class.$this->suffix),
        ];
    }

    public function getExtensions()
    {
        return [];
    }

    /**
     * Returns the entry named PACKAGE.$name, of simply $name if PACKAGE.$name is not found.
     *
     * @param ContainerInterface $container
     * @param string             $name
     *
     * @return mixed
     */
    private static function get(ContainerInterface $container, string $name, $default = null)
    {
        $namespacedName = self::PACKAGE.'.'.$name;

        return $container->has($namespacedName) ? $container->get($namespacedName) : ($container->has($name) ? $container->get($name) : $default);
    }

    public static function createEphemeralDriver() : Ephemeral
    {
        return new Ephemeral();
    }

    public static function createBlackHoleDriver() : BlackHole
    {
        return new BlackHole();
    }
}
