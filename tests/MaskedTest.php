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
     * Runs masked() in a real POSIX subprocess: each typed character must be
     * echoed as the mask character and the real answer returned.
     */
    public function testMaskedEchoesMaskCharactersOnPosix() : void
    {
        if (CLI::isWindows()) {
            self::markTestSkipped('Requires a POSIX terminal for stty based masking.');
        }
        $script = <<<'PHP'
            use Framework\CLI\CLI;

            $answer = CLI::masked('Token', '#');
            \fwrite(\STDOUT, 'answer=' . $answer);
            PHP;
        [$exitCode, $output] = $this->runScript($script, "s3cret\n");
        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Token: ######', $output);
        self::assertStringContainsString('answer=s3cret', $output);
        self::assertStringNotContainsString('s3cret', \str_replace('answer=s3cret', '', $output));
    }

    public function testMaskedCustomMaskCharacter() : void
    {
        if (CLI::isWindows()) {
            self::markTestSkipped('Requires a POSIX terminal for stty based masking.');
        }
        $script = <<<'PHP'
            use Framework\CLI\CLI;

            $answer = CLI::masked('Pin', 'x');
            \fwrite(\STDOUT, 'answer=' . $answer);
            PHP;
        [$exitCode, $output] = $this->runScript($script, "1234\n");
        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Pin: xxxx', $output);
        self::assertStringContainsString('answer=1234', $output);
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
