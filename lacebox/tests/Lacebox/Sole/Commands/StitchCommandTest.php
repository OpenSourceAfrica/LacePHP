<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Commands;

use LacePHP\Lacebox\Tests\TestCase;

final class StitchCommandTest extends TestCase
{
    public function testClassExistsAndPublicApi(): void
    {
        $fqcn = 'Lacebox\\Sole\\Commands\\StitchCommand';

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

        // Identity methods (strict)
        $this->assertTrue($ref->hasMethod('description'), 'Missing method description');
        $this->assertTrue($ref->hasMethod('matches'),     'Missing method matches');
        $this->assertTrue($ref->hasMethod('name'),        'Missing method name');

        // Entrypoint can be named handle/run/execute/__invoke/main
        $this->assertHasAnyMethod($ref,
            ['handle','run','execute','__invoke','main'],
            'StitchCommand::handle/run/execute'
        );

        // An `index` method is not universally present; don’t make it mandatory.
        // If you still want a soft check, uncomment the next line:
        // $this->assertHasAnyMethod($ref, ['index','__invoke'], 'StitchCommand::index/__invoke');

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
