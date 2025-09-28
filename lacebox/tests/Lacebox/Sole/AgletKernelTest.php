<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class AgletKernelTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\AgletKernel') || interface_exists('Lacebox\Sole\AgletKernel') || trait_exists('Lacebox\Sole\AgletKernel'), 'Type not found: Lacebox\Sole\AgletKernel');

        if (class_exists('Lacebox\Sole\AgletKernel')) {
            $ref = new \ReflectionClass('Lacebox\Sole\AgletKernel');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("getCodeTasks"), "Missing method getCodeTasks");
            $this->assertTrue($ref->hasMethod("task"), "Missing method task");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\AgletKernel', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\AgletKernel') || trait_exists('Lacebox\Sole\AgletKernel'));
        }
    }
}
