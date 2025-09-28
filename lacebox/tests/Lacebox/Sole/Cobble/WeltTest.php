<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class WeltTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\Welt') || interface_exists('Lacebox\Sole\Cobble\Welt') || trait_exists('Lacebox\Sole\Cobble\Welt'), 'Type not found: Lacebox\Sole\Cobble\Welt');

        if (class_exists('Lacebox\Sole\Cobble\Welt')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\Welt');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("create"), "Missing method create");
            $this->assertTrue($ref->hasMethod("dropIfExists"), "Missing method dropIfExists");
            $this->assertTrue($ref->hasMethod("table"), "Missing method table");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\Welt', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\Welt') || trait_exists('Lacebox\Sole\Cobble\Welt'));
        }
    }
}
