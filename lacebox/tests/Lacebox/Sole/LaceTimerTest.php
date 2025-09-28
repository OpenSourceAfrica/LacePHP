<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class LaceTimerTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\LaceTimer') || interface_exists('Lacebox\Sole\LaceTimer') || trait_exists('Lacebox\Sole\LaceTimer'), 'Type not found: Lacebox\Sole\LaceTimer');

        if (class_exists('Lacebox\Sole\LaceTimer')) {
            $ref = new \ReflectionClass('Lacebox\Sole\LaceTimer');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("dueTasks"), "Missing method dueTasks");
            $this->assertTrue($ref->hasMethod("loadSchedule"), "Missing method loadSchedule");
            $this->assertTrue($ref->hasMethod("runDue"), "Missing method runDue");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\LaceTimer', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\LaceTimer') || trait_exists('Lacebox\Sole\LaceTimer'));
        }
    }
}
