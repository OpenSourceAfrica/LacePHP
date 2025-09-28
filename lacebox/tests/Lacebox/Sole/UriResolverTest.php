<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class UriResolverTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\UriResolver') || interface_exists('Lacebox\Sole\UriResolver') || trait_exists('Lacebox\Sole\UriResolver'), 'Type not found: Lacebox\Sole\UriResolver');

        if (class_exists('Lacebox\Sole\UriResolver')) {
            $ref = new \ReflectionClass('Lacebox\Sole\UriResolver');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("resolve"), "Missing method resolve");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\UriResolver', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\UriResolver') || trait_exists('Lacebox\Sole\UriResolver'));
        }
    }
}
