<?php

namespace TheCodingMachine;

use Psr\Cache\CacheItemPoolInterface;
use Simplex\Container;
use Stash\Driver\Composite;
use Stash\Pool;

class StashServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testProvider()
    {
        $simplex = new Container();
        $simplex->register(new StashServiceProvider());

        $pool = $simplex->get(CacheItemPoolInterface::class);
        $this->assertInstanceOf(Pool::class, $pool);

        $driver = $pool->getDriver();
        $this->assertInstanceOf(Composite::class, $driver);
    }
}
