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

use Framework\CLI\CLI;
use PHPUnit\Framework\TestCase;

/**
 * Class MaskedTest.
 */
final class MaskedTest extends TestCase
{
    /**
     * Runs masked() in a real POSIX subprocess on a pty: each typed character
     * must be echoed as the mask character and the real answer returned.
     */
    public function testMaskedEchoesMaskCharactersOnPosix() : void
    {
        if (CLI::isWindows()) {
            self::markTestSkipped('Requires a POSIX terminal for stty based masking.');
        }
        \exec('command -v script 2>/dev/null', $check, $found);
        if ($found !== 0 || $check === []) {
            self::markTestSkipped('The script command is required to emulate a TTY.');
        }
        $script = <<<'PHP'
            use Framework\CLI\CLI;

            $answer = CLI::masked('Token', '#');
            \fwrite(\STDOUT, 'answer=' . $answer);
            PHP;
        [$exitCode, $output] = $this->runScript($script, 'pty', 's3cret');
        $output = \str_replace("\r", '', $output);
        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Token: ######', $output);
        self::assertStringContainsString('answer=s3cret', $output);
        self::assertStringNotContainsString('s3cret', \str_replace('answer=s3cret', '', $output));
    }

    /**
     * Without a TTY, masked() reads the line normally: the answer is
     * returned but no mask characters are echoed.
     */
    public function testMaskedWithoutTtyFallsBackToPlainRead() : void
    {
        if (CLI::isWindows()) {
            self::markTestSkipped('Requires a POSIX pipe for the no TTY fallback.');
        }
        $script = <<<'PHP'
            use Framework\CLI\CLI;

            $answer = CLI::masked('Token', '#');
            \fwrite(\STDOUT, 'answer=' . $answer);
            PHP;
        [$exitCode, $output] = $this->runScript($script, 'pipe', 's3cret');
        $output = \str_replace("\r", '', $output);
        self::assertSame(0, $exitCode);
        self::assertStringNotContainsString('######', $output);
        self::assertStringContainsString('answer=s3cret', $output);
    }

    /**
     * Run a script reading from a piped STDIN as a real PHP process.
     *
     * @return array{0:int,1:string}
     */
    private function runScript(string $code, string $stdin) : array
    {
        $autoloader = \dirname(__DIR__) . '/vendor/autoload.php';
        $file = \sys_get_temp_dir() . '/webisters-cli-masked-' . \uniqid() . '.php';
        \file_put_contents(
            $file,
            '<?php require ' . \var_export($autoloader, true) . ";\n" . $code
        );

        $command = 'echo ' . \escapeshellarg($stdin) . ' | '
            . \PHP_BINARY . ' ' . \escapeshellarg($file) . ' 2>&1';
        \exec($command, $output, $exitCode);

        \unlink($file);

        return [$exitCode, \implode("\n", $output)];
    }
}
