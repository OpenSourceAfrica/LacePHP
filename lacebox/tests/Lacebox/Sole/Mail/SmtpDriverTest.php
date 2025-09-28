<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Mail;

use LacePHP\Lacebox\Tests\TestCase;

final class SmtpDriverTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Mail\SmtpDriver') || interface_exists('Lacebox\Sole\Mail\SmtpDriver') || trait_exists('Lacebox\Sole\Mail\SmtpDriver'), 'Type not found: Lacebox\Sole\Mail\SmtpDriver');

        if (class_exists('Lacebox\Sole\Mail\SmtpDriver')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Mail\SmtpDriver');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("send"), "Missing method send");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Mail\SmtpDriver', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Mail\SmtpDriver') || trait_exists('Lacebox\Sole\Mail\SmtpDriver'));
        }
    }
}
