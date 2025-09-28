<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class ConfigLoaderTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\ConfigLoader') || interface_exists('Lacebox\Sole\ConfigLoader') || trait_exists('Lacebox\Sole\ConfigLoader'), 'Type not found: Lacebox\Sole\ConfigLoader');

        if (class_exists('Lacebox\Sole\ConfigLoader')) {
            $ref = new \ReflectionClass('Lacebox\Sole\ConfigLoader');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("load"), "Missing method load");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\ConfigLoader', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\ConfigLoader') || trait_exists('Lacebox\Sole\ConfigLoader'));
        }
    }
}
