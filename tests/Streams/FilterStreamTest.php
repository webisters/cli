<?php
/*
 * This file is part of Webisters CLI Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\CLI\Streams;

use Framework\CLI\Streams\Stderr;
use Framework\CLI\Streams\Stdout;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class FilterStreamTest extends TestCase
{
    public function testConflicts() : void
    {
        Stderr::init();
        Stdout::init();
        \fwrite(\STDERR, 'err');
        \fwrite(\STDOUT, 'out');
        self::assertSame('err', Stderr::getContents());
        self::assertSame('out', Stdout::getContents());
    }
}
