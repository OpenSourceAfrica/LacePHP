<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class ConfigTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Config') || interface_exists('Lacebox\Sole\Config') || trait_exists('Lacebox\Sole\Config'), 'Type not found: Lacebox\Sole\Config');

        if (class_exists('Lacebox\Sole\Config')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Config');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("all"), "Missing method all");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");
            $this->assertTrue($ref->hasMethod("offsetExists"), "Missing method offsetExists");
            $this->assertTrue($ref->hasMethod("offsetGet"), "Missing method offsetGet");
            $this->assertTrue($ref->hasMethod("offsetSet"), "Missing method offsetSet");
            $this->assertTrue($ref->hasMethod("offsetUnset"), "Missing method offsetUnset");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Config', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Config') || trait_exists('Lacebox\Sole\Config'));
        }
    }
}
