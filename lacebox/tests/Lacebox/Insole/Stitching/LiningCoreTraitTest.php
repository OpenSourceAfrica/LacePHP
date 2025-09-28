<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole\Stitching;

use LacePHP\Lacebox\Tests\TestCase;

final class LiningCoreTraitTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\Stitching\LiningCoreTrait') || interface_exists('Lacebox\Insole\Stitching\LiningCoreTrait') || trait_exists('Lacebox\Insole\Stitching\LiningCoreTrait'), 'Type not found: Lacebox\Insole\Stitching\LiningCoreTrait');

        if (class_exists('Lacebox\Insole\Stitching\LiningCoreTrait')) {
            $ref = new \ReflectionClass('Lacebox\Insole\Stitching\LiningCoreTrait');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'trait'
            );

            $this->assertTrue($ref->hasMethod("addRoute"), "Missing method addRoute");
            $this->assertTrue($ref->hasMethod("getRoutes"), "Missing method getRoutes");
            $this->assertTrue($ref->hasMethod("resolve"), "Missing method resolve");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\Stitching\LiningCoreTrait', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\Stitching\LiningCoreTrait') || trait_exists('Lacebox\Insole\Stitching\LiningCoreTrait'));
        }
    }
}
