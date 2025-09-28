<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class EyeletDispatcherTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\EyeletDispatcher') || interface_exists('Lacebox\Sole\EyeletDispatcher') || trait_exists('Lacebox\Sole\EyeletDispatcher'), 'Type not found: Lacebox\Sole\EyeletDispatcher');

        if (class_exists('Lacebox\Sole\EyeletDispatcher')) {
            $ref = new \ReflectionClass('Lacebox\Sole\EyeletDispatcher');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("dispatch"), "Missing method dispatch");
            $this->assertTrue($ref->hasMethod("listen"), "Missing method listen");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\EyeletDispatcher', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\EyeletDispatcher') || trait_exists('Lacebox\Sole\EyeletDispatcher'));
        }
    }
}
