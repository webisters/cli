<?php
/*
 * This file is part of Webisters CLI Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\CLI;

use Framework\CLI\Console;

class ConsoleMock extends Console
{
    public string $command;

    public function prepare(array $argumentValues) : void
    {
        parent::prepare($argumentValues);
    }
}
