<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class RouterInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\RouterInterface') || interface_exists('Lacebox\Shoelace\RouterInterface') || trait_exists('Lacebox\Shoelace\RouterInterface'), 'Type not found: Lacebox\Shoelace\RouterInterface');

        if (class_exists('Lacebox\Shoelace\RouterInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\RouterInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("addRoute"), "Missing method addRoute");
            $this->assertTrue($ref->hasMethod("getRoutes"), "Missing method getRoutes");
            $this->assertTrue($ref->hasMethod("resolve"), "Missing method resolve");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\RouterInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\RouterInterface') || trait_exists('Lacebox\Shoelace\RouterInterface'));
        }
    }
}
