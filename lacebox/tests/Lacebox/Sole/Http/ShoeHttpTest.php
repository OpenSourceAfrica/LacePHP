<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Http;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeHttpTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Http\ShoeHttp') || interface_exists('Lacebox\Sole\Http\ShoeHttp') || trait_exists('Lacebox\Sole\Http\ShoeHttp'), 'Type not found: Lacebox\Sole\Http\ShoeHttp');

        if (class_exists('Lacebox\Sole\Http\ShoeHttp')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Http\ShoeHttp');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("authBasic"), "Missing method authBasic");
            $this->assertTrue($ref->hasMethod("authBearer"), "Missing method authBearer");
            $this->assertTrue($ref->hasMethod("authDigest"), "Missing method authDigest");
            $this->assertTrue($ref->hasMethod("formData"), "Missing method formData");
            $this->assertTrue($ref->hasMethod("header"), "Missing method header");
            $this->assertTrue($ref->hasMethod("json"), "Missing method json");
            $this->assertTrue($ref->hasMethod("method"), "Missing method method");
            $this->assertTrue($ref->hasMethod("option"), "Missing method option");
            $this->assertTrue($ref->hasMethod("raw"), "Missing method raw");
            $this->assertTrue($ref->hasMethod("send"), "Missing method send");
            $this->assertTrue($ref->hasMethod("soap"), "Missing method soap");
            $this->assertTrue($ref->hasMethod("url"), "Missing method url");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Http\ShoeHttp', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Http\ShoeHttp') || trait_exists('Lacebox\Sole\Http\ShoeHttp'));
        }
    }
}
