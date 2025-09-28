<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Tongue;

use LacePHP\Lacebox\Tests\TestCase;

final class GraphQLSchemaTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Tongue\GraphQLSchema') || interface_exists('Lacebox\Tongue\GraphQLSchema') || trait_exists('Lacebox\Tongue\GraphQLSchema'), 'Type not found: Lacebox\Tongue\GraphQLSchema');

        if (class_exists('Lacebox\Tongue\GraphQLSchema')) {
            $ref = new \ReflectionClass('Lacebox\Tongue\GraphQLSchema');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("build"), "Missing method build");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Tongue\GraphQLSchema', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Tongue\GraphQLSchema') || trait_exists('Lacebox\Tongue\GraphQLSchema'));
        }
    }
}
