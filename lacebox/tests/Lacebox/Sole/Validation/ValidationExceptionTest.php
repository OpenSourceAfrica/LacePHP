<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Validation;

use LacePHP\Lacebox\Tests\TestCase;

final class ValidationExceptionTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Validation\ValidationException') || interface_exists('Lacebox\Sole\Validation\ValidationException') || trait_exists('Lacebox\Sole\Validation\ValidationException'), 'Type not found: Lacebox\Sole\Validation\ValidationException');

        if (class_exists('Lacebox\Sole\Validation\ValidationException')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Validation\ValidationException');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("errors"), "Missing method errors");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Validation\ValidationException', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Validation\ValidationException') || trait_exists('Lacebox\Sole\Validation\ValidationException'));
        }
    }
}
