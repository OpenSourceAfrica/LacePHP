<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class DBTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\DB') || interface_exists('Lacebox\Sole\Cobble\DB') || trait_exists('Lacebox\Sole\Cobble\DB'), 'Type not found: Lacebox\Sole\Cobble\DB');

        if (class_exists('Lacebox\Sole\Cobble\DB')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\DB');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("raw"), "Missing method raw");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\DB', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\DB') || trait_exists('Lacebox\Sole\Cobble\DB'));
        }
    }
}
