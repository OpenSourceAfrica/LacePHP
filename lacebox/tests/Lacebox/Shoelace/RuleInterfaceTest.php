<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class RuleInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\RuleInterface') || interface_exists('Lacebox\Shoelace\RuleInterface') || trait_exists('Lacebox\Shoelace\RuleInterface'), 'Type not found: Lacebox\Shoelace\RuleInterface');

        if (class_exists('Lacebox\Shoelace\RuleInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\RuleInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("message"), "Missing method message");
            $this->assertTrue($ref->hasMethod("validate"), "Missing method validate");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\RuleInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\RuleInterface') || trait_exists('Lacebox\Shoelace\RuleInterface'));
        }
    }
}
