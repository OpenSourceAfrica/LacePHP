<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Mail;

use LacePHP\Lacebox\Tests\TestCase;

final class PhpMailDriverTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Mail\PhpMailDriver') || interface_exists('Lacebox\Sole\Mail\PhpMailDriver') || trait_exists('Lacebox\Sole\Mail\PhpMailDriver'), 'Type not found: Lacebox\Sole\Mail\PhpMailDriver');

        if (class_exists('Lacebox\Sole\Mail\PhpMailDriver')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Mail\PhpMailDriver');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("send"), "Missing method send");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Mail\PhpMailDriver', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Mail\PhpMailDriver') || trait_exists('Lacebox\Sole\Mail\PhpMailDriver'));
        }
    }
}
