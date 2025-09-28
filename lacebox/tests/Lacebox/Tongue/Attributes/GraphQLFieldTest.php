<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Tongue\Attributes;

use LacePHP\Lacebox\Tests\TestCase;

final class GraphQLFieldTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Tongue\Attributes\GraphQLField') || interface_exists('Lacebox\Tongue\Attributes\GraphQLField') || trait_exists('Lacebox\Tongue\Attributes\GraphQLField'), 'Type not found: Lacebox\Tongue\Attributes\GraphQLField');

        if (class_exists('Lacebox\Tongue\Attributes\GraphQLField')) {
            $ref = new \ReflectionClass('Lacebox\Tongue\Attributes\GraphQLField');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            // No public methods to assert by name.

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Tongue\Attributes\GraphQLField', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Tongue\Attributes\GraphQLField') || trait_exists('Lacebox\Tongue\Attributes\GraphQLField'));
        }
    }
}
