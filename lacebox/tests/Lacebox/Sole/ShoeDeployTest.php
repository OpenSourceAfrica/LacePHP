<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeDeployTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\ShoeDeploy') || interface_exists('Lacebox\Sole\ShoeDeploy') || trait_exists('Lacebox\Sole\ShoeDeploy'), 'Type not found: Lacebox\Sole\ShoeDeploy');

        if (class_exists('Lacebox\Sole\ShoeDeploy')) {
            $ref = new \ReflectionClass('Lacebox\Sole\ShoeDeploy');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("run"), "Missing method run");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\ShoeDeploy', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\ShoeDeploy') || trait_exists('Lacebox\Sole\ShoeDeploy'));
        }
    }
}
