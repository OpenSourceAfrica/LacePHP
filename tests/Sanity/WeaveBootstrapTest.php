<?php
declare(strict_types=1);
namespace LacePHP\Tests\Sanity;

use LacePHP\Weave\Tests\TestCase;

final class WeaveBootstrapTest extends TestCase {
    public function testTrue(): void {
        $this->assertTrue(true);
    }
}
