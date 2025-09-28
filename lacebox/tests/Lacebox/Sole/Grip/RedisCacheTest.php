<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Grip;

use LacePHP\Lacebox\Tests\TestCase;

final class RedisCacheTest extends TestCase {
    public function testClassExistsAndPublicApi(): void
    {
        // Skip cleanly if redis extension is not installed
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not installed');
        }

        $this->assertTrue(
            class_exists('Lacebox\Sole\Grip\RedisCache') ||
            interface_exists('Lacebox\Sole\Grip\RedisCache') ||
            trait_exists('Lacebox\Sole\Grip\RedisCache'),
            'Type not found: Lacebox\Sole\Grip\RedisCache'
        );

        if (class_exists('Lacebox\Sole\Grip\RedisCache')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Grip\RedisCache');
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
                        $this->assertInstanceOf('Lacebox\Sole\Grip\RedisCache', $obj);
                    } catch (\RuntimeException $e) {
                        $this->markTestSkipped('Redis not configured/running: '.$e->getMessage());
                    }
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(
                interface_exists('Lacebox\Sole\Grip\RedisCache') || trait_exists('Lacebox\Sole\Grip\RedisCache')
            );
        }
    }
}
