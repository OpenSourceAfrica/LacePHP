<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class RouterTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Router') || interface_exists('Lacebox\Sole\Router') || trait_exists('Lacebox\Sole\Router'), 'Type not found: Lacebox\Sole\Router');

        if (class_exists('Lacebox\Sole\Router')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Router');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("__call"), "Missing method __call");
            $this->assertTrue($ref->hasMethod("addRoute"), "Missing method addRoute");
            $this->assertTrue($ref->hasMethod("bind"), "Missing method bind");
            $this->assertTrue($ref->hasMethod("delete"), "Missing method delete");
            $this->assertTrue($ref->hasMethod("dispatch"), "Missing method dispatch");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");
            $this->assertTrue($ref->hasMethod("getRoutes"), "Missing method getRoutes");
            $this->assertTrue($ref->hasMethod("group"), "Missing method group");
            $this->assertTrue($ref->hasMethod("load"), "Missing method load");
            $this->assertTrue($ref->hasMethod("make"), "Missing method make");
            $this->assertTrue($ref->hasMethod("options"), "Missing method options");
            $this->assertTrue($ref->hasMethod("patch"), "Missing method patch");
            $this->assertTrue($ref->hasMethod("post"), "Missing method post");
            $this->assertTrue($ref->hasMethod("put"), "Missing method put");
            $this->assertTrue($ref->hasMethod("resolve"), "Missing method resolve");
            $this->assertTrue($ref->hasMethod("setConfig"), "Missing method setConfig");
            $this->assertTrue($ref->hasMethod("setGlobalMiddleware"), "Missing method setGlobalMiddleware");
            $this->assertTrue($ref->hasMethod("setGuardResolver"), "Missing method setGuardResolver");
            $this->assertTrue($ref->hasMethod("sewRoute"), "Missing method sewRoute");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Router', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Router') || trait_exists('Lacebox\Sole\Router'));
        }
    }
}
