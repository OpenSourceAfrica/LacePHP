<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class ConnectionManagerTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\ConnectionManager') || interface_exists('Lacebox\Sole\Cobble\ConnectionManager') || trait_exists('Lacebox\Sole\Cobble\ConnectionManager'), 'Type not found: Lacebox\Sole\Cobble\ConnectionManager');

        if (class_exists('Lacebox\Sole\Cobble\ConnectionManager')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\ConnectionManager');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("getConnection"), "Missing method getConnection");
            $this->assertTrue($ref->hasMethod("reset"), "Missing method reset");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\ConnectionManager', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\ConnectionManager') || trait_exists('Lacebox\Sole\Cobble\ConnectionManager'));
        }
    }
}
