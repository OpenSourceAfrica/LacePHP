<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class EyeletDispatcherInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\EyeletDispatcherInterface') || interface_exists('Lacebox\Shoelace\EyeletDispatcherInterface') || trait_exists('Lacebox\Shoelace\EyeletDispatcherInterface'), 'Type not found: Lacebox\Shoelace\EyeletDispatcherInterface');

        if (class_exists('Lacebox\Shoelace\EyeletDispatcherInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\EyeletDispatcherInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("dispatch"), "Missing method dispatch");
            $this->assertTrue($ref->hasMethod("listen"), "Missing method listen");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\EyeletDispatcherInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\EyeletDispatcherInterface') || trait_exists('Lacebox\Shoelace\EyeletDispatcherInterface'));
        }
    }
}
