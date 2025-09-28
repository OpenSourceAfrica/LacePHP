<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole\Stitching\Php7;

use LacePHP\Lacebox\Tests\TestCase;

final class Php7DispatcherTraitTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait') || interface_exists('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait') || trait_exists('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait'), 'Type not found: Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait');

        if (class_exists('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait')) {
            $ref = new \ReflectionClass('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'trait'
            );

            $this->assertTrue($ref->hasMethod("dispatch"), "Missing method dispatch");
            $this->assertTrue($ref->hasMethod("handle"), "Missing method handle");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait') || trait_exists('Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait'));
        }
    }
}
