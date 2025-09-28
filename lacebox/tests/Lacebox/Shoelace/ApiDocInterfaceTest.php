<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class ApiDocInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\ApiDocInterface') || interface_exists('Lacebox\Shoelace\ApiDocInterface') || trait_exists('Lacebox\Shoelace\ApiDocInterface'), 'Type not found: Lacebox\Shoelace\ApiDocInterface');

        if (class_exists('Lacebox\Shoelace\ApiDocInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\ApiDocInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("openApiSpec"), "Missing method openApiSpec");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\ApiDocInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\ApiDocInterface') || trait_exists('Lacebox\Shoelace\ApiDocInterface'));
        }
    }
}
