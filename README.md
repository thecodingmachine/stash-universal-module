[![Build Status](https://travis-ci.org/thecodingmachine/stash-universal-module.svg?branch=1.0)](https://travis-ci.org/thecodingmachine/stash-universal-module)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/stash-universal-module/badge.svg?branch=1.0&service=github)](https://coveralls.io/github/thecodingmachine/stash-universal-module?branch=1.0)


# Stash universal module

This package integrates Stash (the PHP PSR-6 compatible cache library) in any [container-interop/service-provider](https://github.com/container-interop/service-provider) compatible framework/container.

## Installation

```
composer require thecodingmachine/stash-universal-module
```

Once installed, you need to register the [`TheCodingMachine\StashServiceProvider`](src/StashServiceProvider.php) into your container.

If your container supports Puli integration, you have nothing to do. Otherwise, refer to your framework or container's documentation to learn how to register *service providers*.

## Introduction

This service provider is meant to create a PSR-6 cache pool `Psr\Cache\CacheItemPoolInterface` instance.

Out of the box, the instance should be usable with sensible defaults. We tried to keep the defaults usable for most of the developer, while still providing best performances for the server.

### Usage

```php
use Psr\Cache\CacheItemPoolInterface

$cachePool = $container->get(CacheItemPoolInterface::class);
echo $cachePool->getItem('my_cached_value')->get();
```

### Default values

By default:

- The default cache pool is a composite pool made of:
    - An ephemeral (in-memory) driver for fast access to already fetched values
    - An APC driver (or a Filesystem driver as fallback if APC is not available)

## Configuration

**Important**: This service provider accepts an optional parameter in the constructor: a "suffix" that can be used if you want many different instances.

```php
use Psr\Cache\CacheItemPoolInterface

// Let's assume we are using Simplex as our container
$container = new Simplex\Container();
// Registers a default service provider
$container->register(new StashServiceProvider());
// Registers another service provider for a shared memcache pool
$container->register(new StashServiceProvider('shared'));

// Lets configure the second service provider.
$container['stash.shared.memcache.options'] = [
    'servers' => ['127.0.0.1', '11211']
];
// Let's override the composite options to put an ephemeral driver and the memcache driver next.
$container['stash.composite.options'] = function(ContainerInterface $container) {
    return [
        $container->get(Ephemeral::class),
        $container->get(Memcache::class)
    ];
}


$defaultCachePool = $container->get(CacheItemPoolInterface::class);
//... do stuff with the default pool

// The shared memcache pool can be accessed by suffixing the instance with ".shared".
$sharedCachePool = $container->get(CacheItemPoolInterface::class.'.shared');
//... do stuff
```

When this service provider looks for a service, it will first look for the service prefixed with the package name, then for the service directly.
So if this documentation states that the `stash.apc.options` entry is used, the service provider will first look into `thecodingmachine.stash-universal-module.stash.apc.options` and then into `stash.apc.options`.
This allows you to keep your container clean (with only one `stash.apc.options` entry), and in case there are several service providers using that `stash.apc.options` entry and you want to pass different values, you can still edit `thecodingmachine.stash-universal-module.stash.apc.options` for this service provider only.


## Expected values / services

This *service provider* expects the following configuration / services to be available:

| Name            | Compulsory | Description                            |
|-----------------|------------|----------------------------------------|
| `stash.apc.options` (or `stash.[suffix].apc.options`) | *no*       | The set of options passed to the APC driver. Defaults to `[]`.  |
| `stash.filesystem.options` (or `stash.[suffix].filesystem.options`) | *no*       | The set of options passed to the Filesystem driver. Defaults to `[]`.  |
| `stash.memcache.options` (or `stash.[suffix].memcache.options`) | *no* (unless you want to use the Memcache driver)       | The [set of options](http://www.stashphp.com/Drivers.html#memcached) passed to the Memcache driver.  |
| `stash.redis.options` (or `stash.[suffix].redis.options`) | *no* (unless you want to use the Redis driver)       | The [set of options](http://www.stashphp.com/Drivers.html#memcached) passed to the Redis driver.  |
| `stash.sqlite.options` (or `stash.[suffix].sqlite.options`) | *no*       | The set of options passed to the SQLite driver. Defaults to `[]`.  |


## Provided services

This *service provider* provides the following services:

| Service name                | Description                          |
|-----------------------------|--------------------------------------|
| `CacheItemPoolInterface::class` (or `CacheItemPoolInterface::class.[suffix]`) | An alias to `Pool::class`.  |
| `Pool::class` (or `Pool::class.[suffix]`) | The default Stash pool. Uses the `DriverInterface::class` default driver.  |
| `PoolInterface::class` (or `PoolInterface::class.[suffix]`) | An alias to `Pool::class`.  |
| `DriverInterface::class` (or `DriverInterface::class.[suffix]`) | The default Stash driver to be used. By default, it is a Composite driver (see below)   |
| `Composite::class` (or `Composite::class.[suffix]`) | The Composite driver. It composes several drivers, specified in the 'stash.composite.options' list   |
| `stash.composite.options` (or `stash.[suffix].composite.options`)  | The options that will be passed to the composite driver. By default, this is a list of drivers, with the Ephemeral driver being the first one, and then APC (if available) or FileSystem (if APC is not available)   |
| `Apc::class` (or `Apc::class.[suffix]`) | The APC driver.  |
| `FileSystem::class` (or `FileSystem::class.[suffix]`) | The Filesystem driver.  |
| `Ephemeral::class` (or `Ephemeral::class.[suffix]`) | The Ephemeral driver.  |
| `BlackHole::class` (or `BlackHole::class.[suffix]`) | The BlackHole driver.  |
| `Memcache::class` (or `Memcache::class.[suffix]`) | The Memcache driver.  |
| `Redis::class` (or `Redis::class.[suffix]`) | The Redis driver.  |
| `Sqlite::class` (or `Sqlite::class.[suffix]`) | The Sqlite driver.  |

## Extended services

This *service provider* does not extend any service.
