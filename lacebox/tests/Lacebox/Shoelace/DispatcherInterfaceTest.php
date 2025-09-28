<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class DispatcherInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\DispatcherInterface') || interface_exists('Lacebox\Shoelace\DispatcherInterface') || trait_exists('Lacebox\Shoelace\DispatcherInterface'), 'Type not found: Lacebox\Shoelace\DispatcherInterface');

        if (class_exists('Lacebox\Shoelace\DispatcherInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\DispatcherInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("dispatch"), "Missing method dispatch");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\DispatcherInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\DispatcherInterface') || trait_exists('Lacebox\Shoelace\DispatcherInterface'));
        }
    }
}
