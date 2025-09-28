<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class ContainerInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\ContainerInterface') || interface_exists('Lacebox\Shoelace\ContainerInterface') || trait_exists('Lacebox\Shoelace\ContainerInterface'), 'Type not found: Lacebox\Shoelace\ContainerInterface');

        if (class_exists('Lacebox\Shoelace\ContainerInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\ContainerInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("bind"), "Missing method bind");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");
            $this->assertTrue($ref->hasMethod("make"), "Missing method make");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\ContainerInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\ContainerInterface') || trait_exists('Lacebox\Shoelace\ContainerInterface'));
        }
    }
}
