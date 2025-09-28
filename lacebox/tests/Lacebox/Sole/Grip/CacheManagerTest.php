<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Grip;

use LacePHP\Lacebox\Tests\TestCase;

final class CacheManagerTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Grip\CacheManager') || interface_exists('Lacebox\Sole\Grip\CacheManager') || trait_exists('Lacebox\Sole\Grip\CacheManager'), 'Type not found: Lacebox\Sole\Grip\CacheManager');

        if (class_exists('Lacebox\Sole\Grip\CacheManager')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Grip\CacheManager');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("driver"), "Missing method driver");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Grip\CacheManager', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Grip\CacheManager') || trait_exists('Lacebox\Sole\Grip\CacheManager'));
        }
    }
}
