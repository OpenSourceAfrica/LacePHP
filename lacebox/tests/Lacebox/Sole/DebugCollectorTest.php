<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class DebugCollectorTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\DebugCollector') || interface_exists('Lacebox\Sole\DebugCollector') || trait_exists('Lacebox\Sole\DebugCollector'), 'Type not found: Lacebox\Sole\DebugCollector');

        if (class_exists('Lacebox\Sole\DebugCollector')) {
            $ref = new \ReflectionClass('Lacebox\Sole\DebugCollector');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("add"), "Missing method add");
            $this->assertTrue($ref->hasMethod("getEntries"), "Missing method getEntries");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\DebugCollector', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\DebugCollector') || trait_exists('Lacebox\Sole\DebugCollector'));
        }
    }
}
