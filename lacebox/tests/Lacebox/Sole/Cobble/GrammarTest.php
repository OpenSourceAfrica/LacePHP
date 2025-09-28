<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class GrammarTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\Grammar') || interface_exists('Lacebox\Sole\Cobble\Grammar') || trait_exists('Lacebox\Sole\Cobble\Grammar'), 'Type not found: Lacebox\Sole\Cobble\Grammar');

        if (class_exists('Lacebox\Sole\Cobble\Grammar')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\Grammar');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("compileAlter"), "Missing method compileAlter");
            $this->assertTrue($ref->hasMethod("compileCreate"), "Missing method compileCreate");
            $this->assertTrue($ref->hasMethod("compileDropIfExists"), "Missing method compileDropIfExists");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\Grammar', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\Grammar') || trait_exists('Lacebox\Sole\Cobble\Grammar'));
        }
    }
}
