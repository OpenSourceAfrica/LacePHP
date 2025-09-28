<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Heel;

use LacePHP\Lacebox\Tests\TestCase;

final class MetricsTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Heel\Metrics') || interface_exists('Lacebox\Heel\Metrics') || trait_exists('Lacebox\Heel\Metrics'), 'Type not found: Lacebox\Heel\Metrics');

        if (class_exists('Lacebox\Heel\Metrics')) {
            $ref = new \ReflectionClass('Lacebox\Heel\Metrics');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("show"), "Missing method show");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Heel\Metrics', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Heel\Metrics') || trait_exists('Lacebox\Heel\Metrics'));
        }
    }
}
