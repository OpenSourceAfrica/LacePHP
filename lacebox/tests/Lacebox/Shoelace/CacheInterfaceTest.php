<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class CacheInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\CacheInterface') || interface_exists('Lacebox\Shoelace\CacheInterface') || trait_exists('Lacebox\Shoelace\CacheInterface'), 'Type not found: Lacebox\Shoelace\CacheInterface');

        if (class_exists('Lacebox\Shoelace\CacheInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\CacheInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

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
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\CacheInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\CacheInterface') || trait_exists('Lacebox\Shoelace\CacheInterface'));
        }
    }
}
