<?php
/*
 * This file is part of Webisters CLI Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\CLI\Commands;

use Framework\CLI\CLI;
use Framework\CLI\Command;

class Host extends Command
{
    protected string $name = 'host';
    protected string $description = 'Host command test';
    protected array $options = [
        '-h, --host' => 'The host to bind.',
    ];

    public function run() : void
    {
        CLI::write('host: ' . \print_r($this->console->getOption('h'), true));
        CLI::write('host value: ' . (string) $this->console->getArgument(0));
    }
}
