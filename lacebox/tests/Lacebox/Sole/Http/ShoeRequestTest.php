<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Http;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeRequestTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Http\ShoeRequest') || interface_exists('Lacebox\Sole\Http\ShoeRequest') || trait_exists('Lacebox\Sole\Http\ShoeRequest'), 'Type not found: Lacebox\Sole\Http\ShoeRequest');

        if (class_exists('Lacebox\Sole\Http\ShoeRequest')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Http\ShoeRequest');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("all"), "Missing method all");
            $this->assertTrue($ref->hasMethod("csrfField"), "Missing method csrfField");
            $this->assertTrue($ref->hasMethod("csrfToken"), "Missing method csrfToken");
            $this->assertTrue($ref->hasMethod("except"), "Missing method except");
            $this->assertTrue($ref->hasMethod("files"), "Missing method files");
            $this->assertTrue($ref->hasMethod("grab"), "Missing method grab");
            $this->assertTrue($ref->hasMethod("header"), "Missing method header");
            $this->assertTrue($ref->hasMethod("input"), "Missing method input");
            $this->assertTrue($ref->hasMethod("ip"), "Missing method ip");
            $this->assertTrue($ref->hasMethod("method"), "Missing method method");
            $this->assertTrue($ref->hasMethod("only"), "Missing method only");
            $this->assertTrue($ref->hasMethod("server"), "Missing method server");
            $this->assertTrue($ref->hasMethod("toArray"), "Missing method toArray");
            $this->assertTrue($ref->hasMethod("uri"), "Missing method uri");
            $this->assertTrue($ref->hasMethod("validateCsrf"), "Missing method validateCsrf");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Http\ShoeRequest', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Http\ShoeRequest') || trait_exists('Lacebox\Sole\Http\ShoeRequest'));
        }
    }
}
