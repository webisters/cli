<?php
declare(strict_types=1);
/*
 * This file is part of Webisters CLI Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\CLI;

use Framework\CLI\Command;

class ZeroCodeThrowingCommandMock extends Command
{
    protected string $name = 'zero-thrower';

    public function run() : void
    {
        throw new \LogicException('zero code');
    }
}
