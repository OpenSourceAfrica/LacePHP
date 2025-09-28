<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Validation\Rules;

use LacePHP\Lacebox\Tests\TestCase;

final class RequiredRuleTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Validation\Rules\RequiredRule') || interface_exists('Lacebox\Sole\Validation\Rules\RequiredRule') || trait_exists('Lacebox\Sole\Validation\Rules\RequiredRule'), 'Type not found: Lacebox\Sole\Validation\Rules\RequiredRule');

        if (class_exists('Lacebox\Sole\Validation\Rules\RequiredRule')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Validation\Rules\RequiredRule');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("message"), "Missing method message");
            $this->assertTrue($ref->hasMethod("validate"), "Missing method validate");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Validation\Rules\RequiredRule', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Validation\Rules\RequiredRule') || trait_exists('Lacebox\Sole\Validation\Rules\RequiredRule'));
        }
    }
}
