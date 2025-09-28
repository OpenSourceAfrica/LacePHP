<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class AbstractPluginCommandTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\AbstractPluginCommand') || interface_exists('Lacebox\Shoelace\AbstractPluginCommand') || trait_exists('Lacebox\Shoelace\AbstractPluginCommand'), 'Type not found: Lacebox\Shoelace\AbstractPluginCommand');

        if (class_exists('Lacebox\Shoelace\AbstractPluginCommand')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\AbstractPluginCommand');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("alias"), "Missing method alias");
            $this->assertTrue($ref->hasMethod("description"), "Missing method description");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\AbstractPluginCommand', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\AbstractPluginCommand') || trait_exists('Lacebox\Shoelace\AbstractPluginCommand'));
        }
    }
}
