<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class EnvTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Env') || interface_exists('Lacebox\Sole\Env') || trait_exists('Lacebox\Sole\Env'), 'Type not found: Lacebox\Sole\Env');

        if (class_exists('Lacebox\Sole\Env')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Env');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("all"), "Missing method all");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Env', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Env') || trait_exists('Lacebox\Sole\Env'));
        }
    }
}
