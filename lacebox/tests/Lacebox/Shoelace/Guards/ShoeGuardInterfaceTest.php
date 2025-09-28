<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace\Guards;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeGuardInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\Guards\ShoeGuardInterface') || interface_exists('Lacebox\Shoelace\Guards\ShoeGuardInterface') || trait_exists('Lacebox\Shoelace\Guards\ShoeGuardInterface'), 'Type not found: Lacebox\Shoelace\Guards\ShoeGuardInterface');

        if (class_exists('Lacebox\Shoelace\Guards\ShoeGuardInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\Guards\ShoeGuardInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("check"), "Missing method check");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\Guards\ShoeGuardInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\Guards\ShoeGuardInterface') || trait_exists('Lacebox\Shoelace\Guards\ShoeGuardInterface'));
        }
    }
}
