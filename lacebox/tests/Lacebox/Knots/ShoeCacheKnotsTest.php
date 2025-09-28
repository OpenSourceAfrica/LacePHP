<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Knots;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeCacheKnotsTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Knots\ShoeCacheKnots') || interface_exists('Lacebox\Knots\ShoeCacheKnots') || trait_exists('Lacebox\Knots\ShoeCacheKnots'), 'Type not found: Lacebox\Knots\ShoeCacheKnots');

        if (class_exists('Lacebox\Knots\ShoeCacheKnots')) {
            $ref = new \ReflectionClass('Lacebox\Knots\ShoeCacheKnots');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("handle"), "Missing method handle");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Knots\ShoeCacheKnots', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Knots\ShoeCacheKnots') || trait_exists('Lacebox\Knots\ShoeCacheKnots'));
        }
    }
}
