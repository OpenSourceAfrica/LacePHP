<?php
declare(strict_types=1);
namespace LacePHP\Framework\Tests\Sanity;

use LacePHP\Lacebox\Tests\TestCase;

final class AutoloadingTest extends TestCase {
    public function testLaceboxFolderExists(): void {
        $this->assertTrue(is_dir(__DIR__ . '/../../../lacebox'));
    }
}
