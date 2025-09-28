<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class StrapInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\StrapInterface') || interface_exists('Lacebox\Shoelace\StrapInterface') || trait_exists('Lacebox\Shoelace\StrapInterface'), 'Type not found: Lacebox\Shoelace\StrapInterface');

        if (class_exists('Lacebox\Shoelace\StrapInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\StrapInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("check"), "Missing method check");
            $this->assertTrue($ref->hasMethod("user"), "Missing method user");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\StrapInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\StrapInterface') || trait_exists('Lacebox\Shoelace\StrapInterface'));
        }
    }
}
