<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Knots;

use LacePHP\Lacebox\Tests\TestCase;

final class RateLimitKnotsTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Knots\RateLimitKnots') || interface_exists('Lacebox\Knots\RateLimitKnots') || trait_exists('Lacebox\Knots\RateLimitKnots'), 'Type not found: Lacebox\Knots\RateLimitKnots');

        if (class_exists('Lacebox\Knots\RateLimitKnots')) {
            $ref = new \ReflectionClass('Lacebox\Knots\RateLimitKnots');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("handle"), "Missing method handle");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Knots\RateLimitKnots', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Knots\RateLimitKnots') || trait_exists('Lacebox\Knots\RateLimitKnots'));
        }
    }
}
