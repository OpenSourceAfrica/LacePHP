<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole;

use LacePHP\Lacebox\Tests\TestCase;

final class LaceVersionTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\LaceVersion') || interface_exists('Lacebox\Insole\LaceVersion') || trait_exists('Lacebox\Insole\LaceVersion'), 'Type not found: Lacebox\Insole\LaceVersion');

        if (class_exists('Lacebox\Insole\LaceVersion')) {
            $ref = new \ReflectionClass('Lacebox\Insole\LaceVersion');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            // No public methods to assert by name.

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\LaceVersion', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\LaceVersion') || trait_exists('Lacebox\Insole\LaceVersion'));
        }
    }
}
