<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Knots;

use LacePHP\Lacebox\Tests\TestCase;

final class CorsKnotsTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Knots\CorsKnots') || interface_exists('Lacebox\Knots\CorsKnots') || trait_exists('Lacebox\Knots\CorsKnots'), 'Type not found: Lacebox\Knots\CorsKnots');

        if (class_exists('Lacebox\Knots\CorsKnots')) {
            $ref = new \ReflectionClass('Lacebox\Knots\CorsKnots');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("handle"), "Missing method handle");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Knots\CorsKnots', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Knots\CorsKnots') || trait_exists('Lacebox\Knots\CorsKnots'));
        }
    }
}
