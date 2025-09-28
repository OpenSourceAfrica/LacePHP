<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class MiddlewareInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\MiddlewareInterface') || interface_exists('Lacebox\Shoelace\MiddlewareInterface') || trait_exists('Lacebox\Shoelace\MiddlewareInterface'), 'Type not found: Lacebox\Shoelace\MiddlewareInterface');

        if (class_exists('Lacebox\Shoelace\MiddlewareInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\MiddlewareInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("handle"), "Missing method handle");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\MiddlewareInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\MiddlewareInterface') || trait_exists('Lacebox\Shoelace\MiddlewareInterface'));
        }
    }
}
