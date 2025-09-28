<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole\Stitching;

use LacePHP\Lacebox\Tests\TestCase;

final class SingletonTraitTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\Stitching\SingletonTrait') || interface_exists('Lacebox\Insole\Stitching\SingletonTrait') || trait_exists('Lacebox\Insole\Stitching\SingletonTrait'), 'Type not found: Lacebox\Insole\Stitching\SingletonTrait');

        if (class_exists('Lacebox\Insole\Stitching\SingletonTrait')) {
            $ref = new \ReflectionClass('Lacebox\Insole\Stitching\SingletonTrait');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'trait'
            );

            $this->assertTrue($ref->hasMethod("getInstance"), "Missing method getInstance");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\Stitching\SingletonTrait', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\Stitching\SingletonTrait') || trait_exists('Lacebox\Insole\Stitching\SingletonTrait'));
        }
    }
}
