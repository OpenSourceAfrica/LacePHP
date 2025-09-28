<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole\Lining;

use LacePHP\Lacebox\Tests\TestCase;

final class Php8LiningTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\Lining\Php8Lining') || interface_exists('Lacebox\Insole\Lining\Php8Lining') || trait_exists('Lacebox\Insole\Lining\Php8Lining'), 'Type not found: Lacebox\Insole\Lining\Php8Lining');

        if (class_exists('Lacebox\Insole\Lining\Php8Lining')) {
            $ref = new \ReflectionClass('Lacebox\Insole\Lining\Php8Lining');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("registerRoutesFromAttributes"), "Missing method registerRoutesFromAttributes");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\Lining\Php8Lining', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\Lining\Php8Lining') || trait_exists('Lacebox\Insole\Lining\Php8Lining'));
        }
    }
}
