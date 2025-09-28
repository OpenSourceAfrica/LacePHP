<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Tongue;

use LacePHP\Lacebox\Tests\TestCase;

final class TunnelServiceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Tongue\TunnelService') || interface_exists('Lacebox\Tongue\TunnelService') || trait_exists('Lacebox\Tongue\TunnelService'), 'Type not found: Lacebox\Tongue\TunnelService');

        if (class_exists('Lacebox\Tongue\TunnelService')) {
            $ref = new \ReflectionClass('Lacebox\Tongue\TunnelService');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("share"), "Missing method share");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Tongue\TunnelService', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Tongue\TunnelService') || trait_exists('Lacebox\Tongue\TunnelService'));
        }
    }
}
