<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Shoelace;

use LacePHP\Lacebox\Tests\TestCase;

final class MailDriverInterfaceTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Shoelace\MailDriverInterface') || interface_exists('Lacebox\Shoelace\MailDriverInterface') || trait_exists('Lacebox\Shoelace\MailDriverInterface'), 'Type not found: Lacebox\Shoelace\MailDriverInterface');

        if (class_exists('Lacebox\Shoelace\MailDriverInterface')) {
            $ref = new \ReflectionClass('Lacebox\Shoelace\MailDriverInterface');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'interface'
            );

            $this->assertTrue($ref->hasMethod("send"), "Missing method send");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Shoelace\MailDriverInterface', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Shoelace\MailDriverInterface') || trait_exists('Lacebox\Shoelace\MailDriverInterface'));
        }
    }
}
