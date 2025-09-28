<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class ModelTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\Model') || interface_exists('Lacebox\Sole\Cobble\Model') || trait_exists('Lacebox\Sole\Cobble\Model'), 'Type not found: Lacebox\Sole\Cobble\Model');

        if (class_exists('Lacebox\Sole\Cobble\Model')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\Model');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("__get"), "Missing method __get");
            $this->assertTrue($ref->hasMethod("__set"), "Missing method __set");
            $this->assertTrue($ref->hasMethod("all"), "Missing method all");
            $this->assertTrue($ref->hasMethod("delete"), "Missing method delete");
            $this->assertTrue($ref->hasMethod("find"), "Missing method find");
            $this->assertTrue($ref->hasMethod("query"), "Missing method query");
            $this->assertTrue($ref->hasMethod("refresh"), "Missing method refresh");
            $this->assertTrue($ref->hasMethod("save"), "Missing method save");
            $this->assertTrue($ref->hasMethod("with"), "Missing method with");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\Model', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\Model') || trait_exists('Lacebox\Sole\Cobble\Model'));
        }
    }
}
