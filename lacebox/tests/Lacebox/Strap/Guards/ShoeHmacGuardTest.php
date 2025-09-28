<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Strap\Guards;

use LacePHP\Lacebox\Tests\TestCase;

final class ShoeHmacGuardTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Strap\Guards\ShoeHmacGuard') || interface_exists('Lacebox\Strap\Guards\ShoeHmacGuard') || trait_exists('Lacebox\Strap\Guards\ShoeHmacGuard'), 'Type not found: Lacebox\Strap\Guards\ShoeHmacGuard');

        if (class_exists('Lacebox\Strap\Guards\ShoeHmacGuard')) {
            $ref = new \ReflectionClass('Lacebox\Strap\Guards\ShoeHmacGuard');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("check"), "Missing method check");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Strap\Guards\ShoeHmacGuard', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Strap\Guards\ShoeHmacGuard') || trait_exists('Lacebox\Strap\Guards\ShoeHmacGuard'));
        }
    }
}
