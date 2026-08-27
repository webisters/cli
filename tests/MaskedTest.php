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
        [$exitCode, $output] = $this->runScript($script, 's3cret', 'pty');
        $output = \str_replace("\r", '', $output);
        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Token: ######', $output);
        self::assertStringContainsString('answer=s3cret', $output);
        // The answer must not appear as an echo after the prompt.
        self::assertStringNotContainsString('Token: s3cret', $output);
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
        [$exitCode, $output] = $this->runScript($script, 's3cret', 'pipe');
        $output = \str_replace("\r", '', $output);
        self::assertSame(0, $exitCode);
        self::assertStringNotContainsString('######', $output);
        self::assertStringContainsString('answer=s3cret', $output);
    }

    /**
     * Run a script as a real PHP process, either with STDIN as a plain pipe
     * or attached to a pseudo terminal (via the script utility) so stty
     * based masking can be exercised.
     *
     * @param string $stdin The text fed to the script on STDIN
     * @param string $mode 'pipe' or 'pty'
     *
     * @return array{0:int,1:string}
     */
    private function runScript(string $code, string $stdin, string $mode = 'pipe') : array
    {
        $autoloader = \dirname(__DIR__) . '/vendor/autoload.php';
        $file = \sys_get_temp_dir() . '/webisters-cli-masked-' . \uniqid() . '.php';
        \file_put_contents(
            $file,
            '<?php require ' . \var_export($autoloader, true) . ";\n" . $code
        );

        $php = \PHP_BINARY . ' ' . \escapeshellarg($file);
        if ($mode === 'pty') {
            $command = 'printf %s\\\n ' . \escapeshellarg($stdin)
                . ' | script -qec ' . \escapeshellarg($php) . ' /dev/null 2>&1';
        } else {
            $command = 'echo ' . \escapeshellarg($stdin) . ' | ' . $php . ' 2>&1';
        }
        \exec($command, $output, $exitCode);

        \unlink($file);

        return [$exitCode, \implode("\n", $output)];
    }
}
