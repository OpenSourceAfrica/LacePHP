<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Commands;

use LacePHP\Lacebox\Tests\TestCase;

final class OutsoleCommandTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Commands\OutsoleCommand') || interface_exists('Lacebox\Sole\Commands\OutsoleCommand') || trait_exists('Lacebox\Sole\Commands\OutsoleCommand'), 'Type not found: Lacebox\Sole\Commands\OutsoleCommand');

        if (class_exists('Lacebox\Sole\Commands\OutsoleCommand')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Commands\OutsoleCommand');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("description"), "Missing method description");
            $this->assertTrue($ref->hasMethod("matches"), "Missing method matches");
            $this->assertTrue($ref->hasMethod("name"), "Missing method name");
            $this->assertTrue($ref->hasMethod("run"), "Missing method run");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Commands\OutsoleCommand', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Commands\OutsoleCommand') || trait_exists('Lacebox\Sole\Commands\OutsoleCommand'));
        }
    }
}
