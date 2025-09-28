<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Mail;

use LacePHP\Lacebox\Tests\TestCase;

final class MailgunDriverTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Mail\MailgunDriver') || interface_exists('Lacebox\Sole\Mail\MailgunDriver') || trait_exists('Lacebox\Sole\Mail\MailgunDriver'), 'Type not found: Lacebox\Sole\Mail\MailgunDriver');

        if (class_exists('Lacebox\Sole\Mail\MailgunDriver')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Mail\MailgunDriver');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("send"), "Missing method send");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Mail\MailgunDriver', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Mail\MailgunDriver') || trait_exists('Lacebox\Sole\Mail\MailgunDriver'));
        }
    }
}
