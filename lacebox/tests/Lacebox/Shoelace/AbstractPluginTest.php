<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class AbstractPluginTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\AbstractPlugin') || interface_exists('Lacebox\Shoelace\AbstractPlugin') || trait_exists('Lacebox\Shoelace\AbstractPlugin'), 'Type not found: Lacebox\Shoelace\AbstractPlugin');

        if (class_exists('Lacebox\Shoelace\AbstractPlugin')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\AbstractPlugin');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("boot"), "Missing method boot");
            $this->assertTrue($ref->hasMethod("register"), "Missing method register");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\AbstractPlugin', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\AbstractPlugin') || trait_exists('Lacebox\Shoelace\AbstractPlugin'));
        }
    }
}
