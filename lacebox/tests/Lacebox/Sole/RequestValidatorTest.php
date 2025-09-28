<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class RequestValidatorTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\RequestValidator') || interface_exists('Lacebox\Sole\RequestValidator') || trait_exists('Lacebox\Sole\RequestValidator'), 'Type not found: Lacebox\Sole\RequestValidator');

        if (class_exists('Lacebox\Sole\RequestValidator')) {
            $ref = new \ReflectionClass('Lacebox\Sole\RequestValidator');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("errors"), "Missing method errors");
            $this->assertTrue($ref->hasMethod("fails"), "Missing method fails");
            $this->assertTrue($ref->hasMethod("first"), "Missing method first");
            $this->assertTrue($ref->hasMethod("lace_break"), "Missing method lace_break");
            $this->assertTrue($ref->hasMethod("setCustomRules"), "Missing method setCustomRules");
            $this->assertTrue($ref->hasMethod("setRules"), "Missing method setRules");
            $this->assertTrue($ref->hasMethod("throwOnFail"), "Missing method throwOnFail");
            $this->assertTrue($ref->hasMethod("validate"), "Missing method validate");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\RequestValidator', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\RequestValidator') || trait_exists('Lacebox\Sole\RequestValidator'));
        }
    }
}
