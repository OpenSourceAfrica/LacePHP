<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole\Stitching;

use LacePHP\Lacebox\Tests\TestCase;

final class RouteUtilsTraitTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\Stitching\RouteUtilsTrait') || interface_exists('Lacebox\Insole\Stitching\RouteUtilsTrait') || trait_exists('Lacebox\Insole\Stitching\RouteUtilsTrait'), 'Type not found: Lacebox\Insole\Stitching\RouteUtilsTrait');

        if (class_exists('Lacebox\Insole\Stitching\RouteUtilsTrait')) {
            $ref = new \ReflectionClass('Lacebox\Insole\Stitching\RouteUtilsTrait');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'trait'
            );

            // No public methods to assert by name.

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\Stitching\RouteUtilsTrait', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\Stitching\RouteUtilsTrait') || trait_exists('Lacebox\Insole\Stitching\RouteUtilsTrait'));
        }
    }
}
