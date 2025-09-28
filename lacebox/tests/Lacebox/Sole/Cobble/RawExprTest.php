<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class RawExprTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\RawExpr') || interface_exists('Lacebox\Sole\Cobble\RawExpr') || trait_exists('Lacebox\Sole\Cobble\RawExpr'), 'Type not found: Lacebox\Sole\Cobble\RawExpr');

        if (class_exists('Lacebox\Sole\Cobble\RawExpr')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\RawExpr');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("__toString"), "Missing method __toString");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\RawExpr', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\RawExpr') || trait_exists('Lacebox\Sole\Cobble\RawExpr'));
        }
    }
}
