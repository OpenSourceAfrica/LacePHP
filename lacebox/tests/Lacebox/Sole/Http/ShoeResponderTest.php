<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Http;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeResponderTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Http\ShoeResponder') || interface_exists('Lacebox\Sole\Http\ShoeResponder') || trait_exists('Lacebox\Sole\Http\ShoeResponder'), 'Type not found: Lacebox\Sole\Http\ShoeResponder');

        if (class_exists('Lacebox\Sole\Http\ShoeResponder')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Http\ShoeResponder');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("error"), "Missing method error");
            $this->assertTrue($ref->hasMethod("getHeaders"), "Missing method getHeaders");
            $this->assertTrue($ref->hasMethod("html"), "Missing method html");
            $this->assertTrue($ref->hasMethod("json"), "Missing method json");
            $this->assertTrue($ref->hasMethod("notFound"), "Missing method notFound");
            $this->assertTrue($ref->hasMethod("serverError"), "Missing method serverError");
            $this->assertTrue($ref->hasMethod("setHeader"), "Missing method setHeader");
            $this->assertTrue($ref->hasMethod("text"), "Missing method text");
            $this->assertTrue($ref->hasMethod("toArray"), "Missing method toArray");
            $this->assertTrue($ref->hasMethod("unauthorized"), "Missing method unauthorized");
            $this->assertTrue($ref->hasMethod("withData"), "Missing method withData");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Http\ShoeResponder', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Http\ShoeResponder') || trait_exists('Lacebox\Sole\Http\ShoeResponder'));
        }
    }
}
