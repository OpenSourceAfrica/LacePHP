<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Heel;

use LacePHP\Lacebox\Tests\TestCase;

final class DashboardTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Heel\Dashboard') || interface_exists('Lacebox\Heel\Dashboard') || trait_exists('Lacebox\Heel\Dashboard'), 'Type not found: Lacebox\Heel\Dashboard');

        if (class_exists('Lacebox\Heel\Dashboard')) {
            $ref = new \ReflectionClass('Lacebox\Heel\Dashboard');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("show"), "Missing method show");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Heel\Dashboard', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Heel\Dashboard') || trait_exists('Lacebox\Heel\Dashboard'));
        }
    }
}
