<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class SocklinerTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Sockliner') || interface_exists('Lacebox\Sole\Sockliner') || trait_exists('Lacebox\Sole\Sockliner'), 'Type not found: Lacebox\Sole\Sockliner');

        if (class_exists('Lacebox\Sole\Sockliner')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Sockliner');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("container"), "Missing method container");
            $this->assertTrue($ref->hasMethod("dispatcher"), "Missing method dispatcher");
            $this->assertTrue($ref->hasMethod("getConfig"), "Missing method getConfig");
            $this->assertTrue($ref->hasMethod("getRouter"), "Missing method getRouter");
            $this->assertTrue($ref->hasMethod("run"), "Missing method run");
            $this->assertTrue($ref->hasMethod("terminate"), "Missing method terminate");
            $this->assertTrue($ref->hasMethod("test"), "Missing method test");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Sockliner', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Sockliner') || trait_exists('Lacebox\Sole\Sockliner'));
        }
    }
}
