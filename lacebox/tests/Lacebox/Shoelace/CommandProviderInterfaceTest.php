<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class CommandProviderInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\CommandProviderInterface') || interface_exists('Lacebox\Shoelace\CommandProviderInterface') || trait_exists('Lacebox\Shoelace\CommandProviderInterface'), 'Type not found: Lacebox\Shoelace\CommandProviderInterface');

        if (class_exists('Lacebox\Shoelace\CommandProviderInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\CommandProviderInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("registerCommands"), "Missing method registerCommands");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\CommandProviderInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\CommandProviderInterface') || trait_exists('Lacebox\Shoelace\CommandProviderInterface'));
        }
    }
}
