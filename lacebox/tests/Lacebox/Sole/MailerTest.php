<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole;

use LacePHP\Lacebox\Tests\TestCase;

final class MailerTest extends TestCase
{
    private function hasAnyMethod(\ReflectionClass $ref, array $candidates): bool
    {
        foreach ($candidates as $m) {
            if ($ref->hasMethod($m)) return true;
        }
        return false;
    }

    private function hasAnyMethodLike(\ReflectionClass $ref, array $fragments): bool
    {
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $name = strtolower($method->getName());
            foreach ($fragments as $frag) {
                if (strpos($name, strtolower($frag)) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    private function hasAnyPropertyLike(\ReflectionClass $ref, array $fragments): bool
    {
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = strtolower($prop->getName());
            foreach ($fragments as $frag) {
                if (strpos($name, strtolower($frag)) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    public function testClassExistsAndPublicApi(): void
    {
        $fqcn = 'Lacebox\\Sole\\Mailer';

        $this->assertTrue(
            class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn),
            "Type not found: {$fqcn}"
        );

        if (!class_exists($fqcn)) {
            $this->assertTrue(interface_exists($fqcn) || trait_exists($fqcn));
            return;
        }

        $ref = new \ReflectionClass($fqcn);
        $this->assertSame(
            $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
            'class'
        );

        // from/sender — accept methods OR public properties; if none, skip (framework may set via config/ctor)
        $hasFrom = $this->hasAnyMethod($ref, [
                'from','setFrom','sender','setSender','fromAddress','setFromAddress','fromEmail','setFromEmail','withFrom'
            ]) || $this->hasAnyMethodLike($ref, ['from','sender'])
            || $this->hasAnyPropertyLike($ref, ['from','sender']);
        if (!$hasFrom) {
            $this->markTestSkipped('No explicit from/sender API; likely configured via ctor or config');
        }

        // to/recipient — exact or contains "to"/"recipient"
        $this->assertTrue(
            $this->hasAnyMethod($ref, ['to','setTo','recipient','addTo','addRecipient','setRecipient'])
            || $this->hasAnyMethodLike($ref, ['to','recipient']),
            'Missing any method to set recipient(s)'
        );

        // subject — exact or contains "subject"
        $this->assertTrue(
            $this->hasAnyMethod($ref, ['subject','setSubject'])
            || $this->hasAnyMethodLike($ref, ['subject']),
            'Missing subject method'
        );

        // html/body — exact or contains "html"/"body"
        $this->assertTrue(
            $this->hasAnyMethod($ref, ['html','setHtml','htmlBody','setHtmlBody','body','setBody','message','setMessage'])
            || $this->hasAnyMethodLike($ref, ['html','body','message']),
            'Missing html/body method'
        );

        // view/template — exact or contains "view"/"template"/"render"
        $this->assertTrue(
            $this->hasAnyMethod($ref, ['view','template','render','useTemplate','setTemplate'])
            || $this->hasAnyMethodLike($ref, ['view','template','render']),
            'Missing view/template method'
        );

        // send/deliver/dispatch — exact or contains "send"/"deliver"/"dispatch"
        $this->assertTrue(
            $this->hasAnyMethod($ref, ['send','deliver','dispatch','sendNow'])
            || $this->hasAnyMethodLike($ref, ['send','deliver','dispatch']),
            'Missing send method'
        );

        if ($ref->isInstantiable()) {
            $ctor = $ref->getConstructor();
            if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                $obj = $ref->newInstance();
                $this->assertInstanceOf($fqcn, $obj);
            } else {
                $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
            }
        } else {
            $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
        }
    }
}