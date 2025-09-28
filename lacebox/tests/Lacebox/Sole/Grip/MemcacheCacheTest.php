<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Grip;

use LacePHP\Lacebox\Tests\TestCase;

final class MemcacheCacheTest extends TestCase {

    public function testClassExistsAndPublicApi(): void
    {
        // Skip cleanly if neither extension is installed
        if (!extension_loaded('memcached') && !extension_loaded('memcache')) {
            $this->markTestSkipped('Memcached/Memcache extension not installed');
        }

        $this->assertTrue(
            class_exists('Lacebox\Sole\Grip\MemcacheCache') ||
            interface_exists('Lacebox\Sole\Grip\MemcacheCache') ||
            trait_exists('Lacebox\Sole\Grip\MemcacheCache'),
            'Type not found: Lacebox\Sole\Grip\MemcacheCache'
        );

        if (class_exists('Lacebox\Sole\Grip\MemcacheCache')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Grip\MemcacheCache');
            $this->assertSame($ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'), 'class');

            $this->assertTrue($ref->hasMethod("clear"), "Missing method clear");
            $this->assertTrue($ref->hasMethod("decrement"), "Missing method decrement");
            $this->assertTrue($ref->hasMethod("delete"), "Missing method delete");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");
            $this->assertTrue($ref->hasMethod("has"), "Missing method has");
            $this->assertTrue($ref->hasMethod("increment"), "Missing method increment");
            $this->assertTrue($ref->hasMethod("remember"), "Missing method remember");
            $this->assertTrue($ref->hasMethod("set"), "Missing method set");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    try {
                        $obj = $ref->newInstance();
                        $this->assertInstanceOf('Lacebox\Sole\Grip\MemcacheCache', $obj);
                    } catch (\RuntimeException $e) {
                        // In case the class throws when the extension/server isn't actually usable
                        $this->markTestSkipped('Memcache(d) not configured: '.$e->getMessage());
                    }
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(
                interface_exists('Lacebox\Sole\Grip\MemcacheCache') || trait_exists('Lacebox\Sole\Grip\MemcacheCache')
            );
        }
    }
}
