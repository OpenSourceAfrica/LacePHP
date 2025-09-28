<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class MigrationManagerTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\MigrationManager') || interface_exists('Lacebox\Sole\Cobble\MigrationManager') || trait_exists('Lacebox\Sole\Cobble\MigrationManager'), 'Type not found: Lacebox\Sole\Cobble\MigrationManager');

        if (class_exists('Lacebox\Sole\Cobble\MigrationManager')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\MigrationManager');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("getRan"), "Missing method getRan");
            $this->assertTrue($ref->hasMethod("markRan"), "Missing method markRan");
            $this->assertTrue($ref->hasMethod("runAll"), "Missing method runAll");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\MigrationManager', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\MigrationManager') || trait_exists('Lacebox\Sole\Cobble\MigrationManager'));
        }
    }
}
