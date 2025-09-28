<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Heel;

use LacePHP\Lacebox\Tests\TestCase;

final class GraphQLEndpointTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Heel\GraphQLEndpoint') || interface_exists('Lacebox\Heel\GraphQLEndpoint') || trait_exists('Lacebox\Heel\GraphQLEndpoint'), 'Type not found: Lacebox\Heel\GraphQLEndpoint');

        if (class_exists('Lacebox\Heel\GraphQLEndpoint')) {
            $ref = new \ReflectionClass('Lacebox\Heel\GraphQLEndpoint');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("execute"), "Missing method execute");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Heel\GraphQLEndpoint', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Heel\GraphQLEndpoint') || trait_exists('Lacebox\Heel\GraphQLEndpoint'));
        }
    }
}
