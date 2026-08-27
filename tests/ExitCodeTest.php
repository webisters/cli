<?php
/*
 * This file is part of Webisters CLI Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information,
 * please view the LICENSE
 * file that was distributed with this source code.
 */
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

use PHPUnit\Framework\TestCase;

/**
 * Covers command dispatch and process exit codes using real subprocesses,
 * since exit codes cannot be asserted inside the PHPUnit process itself.
 */
final class ExitCodeTest extends TestCase
{
    public function testSuccessfulCommandExitsWithZero() : void
    {
        $script = <<<'PHP'

            use Framework\CLI\CLI;
            use Framework\CLI\Command;

            class OkCommand extends Command
            {
                protected string $name = 'ok';

                public function run() : void
                {
                    CLI::write('done');
                }
            }

            (new Framework\CLI\Console())->addCommand(OkCommand::class)->run();
            PHP;
        [$exitCode] = $this->runScript($script, ['ok']);
        self::assertSame(0, $exitCode);
    }

    public function testCommandNotFoundExitsWithOne() : void
    {
        $script = <<<'PHP'

            (new Framework\CLI\Console())->run();
            PHP;
        [$exitCode] = $this->runScript($script, ['this-command-does-not-exist']);
        self::assertSame(1, $exitCode);
    }

    public function testErrorHelperExitsWithGivenCode() : void
    {
        $script = <<<'PHP'

            \Framework\CLI\CLI::error('boom', 3);
            PHP;
        [$exitCode] = $this->runScript($script, []);
        self::assertSame(3, $exitCode);
    }

    /**
     * Run a script as a real PHP process and return its exit code and output.
     *
     * @param array<int,string> $argv The arguments passed to the script after its own name
     *
     * @return array{0:int,1:string}
     */
    private function runScript(string $code, array $argv) : array
    {
        $autoloader = \dirname(__DIR__) . '/vendor/autoload.php';
        $file = \sys_get_temp_dir() . '/webisters-cli-exit-' . \uniqid() . '.php';
        \file_put_contents(
            $file,
            '<?php require ' . \var_export($autoloader, true) . ";\n" . $code
        );

        $command = \PHP_BINARY . ' ' . \escapeshellarg($file);
        foreach ($argv as $argument) {
            $command .= ' ' . \escapeshellarg($argument);
        }

        \exec($command . ' 2>&1', $output, $exitCode);

        \unlink($file);

        return [$exitCode, \implode("\n", $output)];
    }
}
