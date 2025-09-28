<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class MetricsCollectorTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\MetricsCollector') || interface_exists('Lacebox\Sole\MetricsCollector') || trait_exists('Lacebox\Sole\MetricsCollector'), 'Type not found: Lacebox\Sole\MetricsCollector');

        if (class_exists('Lacebox\Sole\MetricsCollector')) {
            $ref = new \ReflectionClass('Lacebox\Sole\MetricsCollector');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("getCounters"), "Missing method getCounters");
            $this->assertTrue($ref->hasMethod("getTimings"), "Missing method getTimings");
            $this->assertTrue($ref->hasMethod("increment"), "Missing method increment");
            $this->assertTrue($ref->hasMethod("recordTiming"), "Missing method recordTiming");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\MetricsCollector', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\MetricsCollector') || trait_exists('Lacebox\Sole\MetricsCollector'));
        }
    }
}
