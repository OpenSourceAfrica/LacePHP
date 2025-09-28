<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class CommandInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\CommandInterface') || interface_exists('Lacebox\Shoelace\CommandInterface') || trait_exists('Lacebox\Shoelace\CommandInterface'), 'Type not found: Lacebox\Shoelace\CommandInterface');

        if (class_exists('Lacebox\Shoelace\CommandInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\CommandInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("description"), "Missing method description");
            $this->assertTrue($ref->hasMethod("matches"), "Missing method matches");
            $this->assertTrue($ref->hasMethod("name"), "Missing method name");
            $this->assertTrue($ref->hasMethod("run"), "Missing method run");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\CommandInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\CommandInterface') || trait_exists('Lacebox\Shoelace\CommandInterface'));
        }
    }
}
