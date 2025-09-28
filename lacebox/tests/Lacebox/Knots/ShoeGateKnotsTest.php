<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Knots;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeGateKnotsTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Knots\ShoeGateKnots') || interface_exists('Lacebox\Knots\ShoeGateKnots') || trait_exists('Lacebox\Knots\ShoeGateKnots'), 'Type not found: Lacebox\Knots\ShoeGateKnots');

        if (class_exists('Lacebox\Knots\ShoeGateKnots')) {
            $ref = new \ReflectionClass('Lacebox\Knots\ShoeGateKnots');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("handle"), "Missing method handle");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Knots\ShoeGateKnots', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Knots\ShoeGateKnots') || trait_exists('Lacebox\Knots\ShoeGateKnots'));
        }
    }
}
