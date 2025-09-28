<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Heel;

use LacePHP\Lacebox\Tests\TestCase;

final class TesterTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Heel\Tester') || interface_exists('Lacebox\Heel\Tester') || trait_exists('Lacebox\Heel\Tester'), 'Type not found: Lacebox\Heel\Tester');

        if (class_exists('Lacebox\Heel\Tester')) {
            $ref = new \ReflectionClass('Lacebox\Heel\Tester');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("expect"), "Missing method expect");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");
            $this->assertTrue($ref->hasMethod("json"), "Missing method json");
            $this->assertTrue($ref->hasMethod("raw"), "Missing method raw");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Heel\Tester', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Heel\Tester') || trait_exists('Lacebox\Heel\Tester'));
        }
    }
}
