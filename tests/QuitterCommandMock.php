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

use Framework\CLI\Command;

class QuitterCommandMock extends Command
{
    protected string $name = 'quitter';

    public function run() : void
    {
        $code = $this->getConsole()->getArgument(0);
        $this->setExitCode($code === null ? 0 : (int) $code);
    }
}
