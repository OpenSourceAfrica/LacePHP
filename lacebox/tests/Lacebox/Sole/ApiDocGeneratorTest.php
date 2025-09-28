<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class ApiDocGeneratorTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\ApiDocGenerator') || interface_exists('Lacebox\Sole\ApiDocGenerator') || trait_exists('Lacebox\Sole\ApiDocGenerator'), 'Type not found: Lacebox\Sole\ApiDocGenerator');

        if (class_exists('Lacebox\Sole\ApiDocGenerator')) {
            $ref = new \ReflectionClass('Lacebox\Sole\ApiDocGenerator');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("generate"), "Missing method generate");
            $this->assertTrue($ref->hasMethod("toJsonFile"), "Missing method toJsonFile");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\ApiDocGenerator', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\ApiDocGenerator') || trait_exists('Lacebox\Sole\ApiDocGenerator'));
        }
    }
}
