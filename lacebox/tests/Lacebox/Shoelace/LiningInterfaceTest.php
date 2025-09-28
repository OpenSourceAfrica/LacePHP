<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class LiningInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\LiningInterface') || interface_exists('Lacebox\Shoelace\LiningInterface') || trait_exists('Lacebox\Shoelace\LiningInterface'), 'Type not found: Lacebox\Shoelace\LiningInterface');

        if (class_exists('Lacebox\Shoelace\LiningInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\LiningInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            // No public methods to assert by name.

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\LiningInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\LiningInterface') || trait_exists('Lacebox\Shoelace\LiningInterface'));
        }
    }
}
