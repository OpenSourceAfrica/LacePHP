<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class PluginManagerTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\PluginManager') || interface_exists('Lacebox\Sole\PluginManager') || trait_exists('Lacebox\Sole\PluginManager'), 'Type not found: Lacebox\Sole\PluginManager');

        if (class_exists('Lacebox\Sole\PluginManager')) {
            $ref = new \ReflectionClass('Lacebox\Sole\PluginManager');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("bootAll"), "Missing method bootAll");
            $this->assertTrue($ref->hasMethod("discoverFromComposer"), "Missing method discoverFromComposer");
            $this->assertTrue($ref->hasMethod("discoverFromFolder"), "Missing method discoverFromFolder");
            $this->assertTrue($ref->hasMethod("getCommands"), "Missing method getCommands");
            $this->assertTrue($ref->hasMethod("getPlugins"), "Missing method getPlugins");
            $this->assertTrue($ref->hasMethod("registerAll"), "Missing method registerAll");
            $this->assertTrue($ref->hasMethod("registerCommands"), "Missing method registerCommands");
            $this->assertTrue($ref->hasMethod("registerProvider"), "Missing method registerProvider");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\PluginManager', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\PluginManager') || trait_exists('Lacebox\Sole\PluginManager'));
        }
    }
}
