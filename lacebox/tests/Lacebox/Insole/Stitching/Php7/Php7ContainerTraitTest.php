<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole\Stitching\Php7;

use LacePHP\Lacebox\Tests\TestCase;

final class Php7ContainerTraitTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait') || interface_exists('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait') || trait_exists('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait'), 'Type not found: Lacebox\Insole\Stitching\Php7\Php7ContainerTrait');

        if (class_exists('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait')) {
            $ref = new \ReflectionClass('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'trait'
            );

            $this->assertTrue($ref->hasMethod("bind"), "Missing method bind");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");
            $this->assertTrue($ref->hasMethod("make"), "Missing method make");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait') || trait_exists('Lacebox\Insole\Stitching\Php7\Php7ContainerTrait'));
        }
    }
}
