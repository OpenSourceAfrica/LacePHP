<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeResponderInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\ShoeResponderInterface') || interface_exists('Lacebox\Shoelace\ShoeResponderInterface') || trait_exists('Lacebox\Shoelace\ShoeResponderInterface'), 'Type not found: Lacebox\Shoelace\ShoeResponderInterface');

        if (class_exists('Lacebox\Shoelace\ShoeResponderInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\ShoeResponderInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("error"), "Missing method error");
            $this->assertTrue($ref->hasMethod("getHeaders"), "Missing method getHeaders");
            $this->assertTrue($ref->hasMethod("html"), "Missing method html");
            $this->assertTrue($ref->hasMethod("json"), "Missing method json");
            $this->assertTrue($ref->hasMethod("notFound"), "Missing method notFound");
            $this->assertTrue($ref->hasMethod("serverError"), "Missing method serverError");
            $this->assertTrue($ref->hasMethod("setHeader"), "Missing method setHeader");
            $this->assertTrue($ref->hasMethod("text"), "Missing method text");
            $this->assertTrue($ref->hasMethod("unauthorized"), "Missing method unauthorized");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\ShoeResponderInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\ShoeResponderInterface') || trait_exists('Lacebox\Shoelace\ShoeResponderInterface'));
        }
    }
}
