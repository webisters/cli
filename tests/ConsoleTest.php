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
use Framework\CLI\Command;
use Framework\CLI\Streams\Stderr;
use Framework\CLI\Streams\Stdout;
use Framework\Language\Language;
use PHPUnit\Framework\TestCase;

final class ConsoleTest extends TestCase
{
    protected ConsoleMock $console;

    protected function setUp() : void
    {
        Stdout::init();
        $this->console = new ConsoleMock();
    }

    protected function tearDown() : void
    {
        Stdout::reset();
    }

    public function testLanguage() : void
    {
        $language = new Language();
        $console = new ConsoleMock($language);
        self::assertSame($language, $console->getLanguage());
        self::assertContains(
            \realpath(__DIR__ . '/../src/Languages') . \DIRECTORY_SEPARATOR,
            $console->getLanguage()->getDirectories()
        );
    }

    public function testEmptyLine() : void
    {
        $this->console->prepare([
            'file.php',
        ]);
        self::assertSame('', $this->console->command);
        self::assertSame([], $this->console->getOptions());
        self::assertSame([], $this->console->getArguments());
    }

    public function testEmptyArgumentDoesNotWarn() : void
    {
        $this->console->prepare([
            'file.php',
            'command',
            '',
        ]);
        self::assertSame('command', $this->console->command);
        self::assertSame([
            '',
        ], $this->console->getArguments());
    }

    public function testExecWithTrailingSpaceKeepsEmptyArgument() : void
    {
        $this->console->addCommand(new CommandMock($this->console));
        $this->console->exec('test ');
        self::assertSame([
            '',
        ], $this->console->getArguments());
    }

    public function testCommandLine() : void
    {
        $this->console->prepare([
            'file.php',
            'command',
        ]);
        self::assertSame('command', $this->console->command);
        self::assertSame([], $this->console->getOptions());
        self::assertSame([], $this->console->getArguments());
        $this->console->prepare([
            'file.php',
            'xx',
        ]);
        self::assertSame('xx', $this->console->command);
        self::assertSame([], $this->console->getOptions());
        self::assertSame([], $this->console->getArguments());
    }

    public function testOptionsLine() : void
    {
        $this->console->prepare([
            'file.php',
            'command',
            '-x',
            '-short',
            '--long',
            '--long-value=10',
            '-y=10',
        ]);
        self::assertSame('command', $this->console->command);
        self::assertSame([
            'x' => true,
            's' => true,
            'h' => true,
            'o' => true,
            'r' => true,
            't' => true,
            'long' => true,
            'long-value' => '10',
            'y' => true,
            '=' => true,
            1 => true,
            0 => true,
        ], $this->console->getOptions());
        self::assertSame([], $this->console->getArguments());
    }

    public function testArgumentsLine() : void
    {
        $this->console->prepare([
            'file.php',
            'command',
            'z',
            '-a',
            'x',
        ]);
        self::assertSame([
            'a' => true,
        ], $this->console->getOptions());
        self::assertSame([
            'z',
            'x',
        ], $this->console->getArguments());
        $this->console->prepare([
            'file.php',
            'command',
            '--',
            'z',
            '-a',
            'x',
        ]);
        self::assertSame([], $this->console->getOptions());
        self::assertSame([
            'z',
            '-a',
            'x',
        ], $this->console->getArguments());
        $this->console->prepare([
            'file.php',
            'command',
            '-i',
            '-j',
            '--',
            'z',
            '-a',
            'x',
        ]);
        self::assertSame(['i' => true, 'j' => true], $this->console->getOptions());
        self::assertSame([
            'z',
            '-a',
            'x',
        ], $this->console->getArguments());
    }

    public function testDefaultCommands() : void
    {
        self::assertNotEmpty($this->console->getCommands());
        self::assertNotNull($this->console->getCommand('index'));
        self::assertNotNull($this->console->getCommand('about'));
        self::assertNotNull($this->console->getCommand('help'));
        self::assertNull($this->console->getCommand('foo'));
    }

    public function testCommands() : void
    {
        $command = new CommandMock($this->console);
        $this->console->addCommands([$command]);
        self::assertNotEmpty($this->console->getCommands());
        self::assertInstanceOf(Command::class, $this->console->getCommand('test'));
    }

    public function testInactiveCommand() : void
    {
        $inactiveCommand = new class($this->console) extends CommandMock {
            protected string $name = 'foo';
            protected bool $active = false;
        };
        $this->console->addCommand($inactiveCommand);
        self::assertNull($this->console->getCommand('foo'));
        foreach ($this->console->getCommands() as $command) {
            self::assertNotSame($inactiveCommand, $command);
        }
    }

    public function testRemoveCommands() : void
    {
        self::assertNotNull($this->console->getCommand('about'));
        self::assertNotNull($this->console->getCommand('help'));
        $this->console->removeCommands(['about', 'help']);
        self::assertNull($this->console->getCommand('about'));
        self::assertNull($this->console->getCommand('help'));
    }

    public function testHasCommand() : void
    {
        self::assertTrue($this->console->hasCommand('about'));
        $this->console->removeCommand('about');
        self::assertFalse($this->console->hasCommand('about'));
    }

    public function testCommandString() : void
    {
        $this->console->addCommands([
            CommandMock::class,
        ]);
        self::assertNotEmpty($this->console->getCommands());
    }

    public function testCommandIndex() : void
    {
        $this->console->run();
        self::assertStringContainsString('index', Stdout::getContents());
    }

    public function _testCommandNotFound() : void
    {
        // TODO: Exit breaks the test
        $this->console->prepare(['file.php', 'unknown']);
        $this->console->run();
    }

    public function testRun() : void
    {
        $this->console->prepare([
            'file.php',
            'test',
            '--option=foo',
            '-o',
            'argument0',
            'argument1',
        ]);
        $this->console->addCommand(new CommandMock($this->console));
        $this->console->run();
        self::assertSame($this->getContentsOfCommandMock(), Stdout::getContents());
    }

    public function testRunWithInvalidCommand() : void
    {
        $this->console->prepare([
            'file.php',
            'unknown',
        ]);
        Stderr::init();
        $this->console->run();
        self::assertStringContainsString(
            'Command not found: "unknown"',
            Stderr::getContents()
        );
    }

    public function testRunWithSuggestion() : void
    {
        $this->console->prepare([
            'file.php',
            'hepl',
        ]);
        Stderr::reset();
        Stderr::init();
        $this->console->run();
        self::assertStringContainsString(
            'Command not found: "hepl"',
            Stderr::getContents()
        );
        self::assertStringContainsString(
            'Did you mean "help"?',
            Stderr::getContents()
        );
    }

    public function testUnknownFarCommandHasNoSuggestion() : void
    {
        $this->console->prepare([
            'file.php',
            'zzzzzzzzzz',
        ]);
        Stderr::reset();
        Stderr::init();
        $this->console->run();
        self::assertStringContainsString(
            'Command not found: "zzzzzzzzzz"',
            Stderr::getContents()
        );
        self::assertStringNotContainsString('Did you mean', Stderr::getContents());
    }

    public function testNoSuggestionForDeactivatedCommand() : void
    {
        $ghost = new class($this->console) extends CommandMock {
            protected string $name = 'ghost';
        };
        $ghost->deactivate();
        $this->console->addCommand($ghost);
        $this->console->prepare([
            'file.php',
            'ghos',
        ]);
        Stderr::reset();
        Stderr::init();
        $this->console->run();
        self::assertStringContainsString(
            'Command not found: "ghos"',
            Stderr::getContents()
        );
        self::assertStringNotContainsString('Did you mean', Stderr::getContents());
    }

    public function testSuggestsClosestAlias() : void
    {
        $command = new CommandMock($this->console);
        $command->setAliases(['list']);
        $this->console->addCommand($command);
        $this->console->prepare([
            'file.php',
            'lst',
        ]);
        Stderr::reset();
        Stderr::init();
        $this->console->run();
        self::assertStringContainsString(
            'Did you mean "ls"?',
            Stderr::getContents()
        );
    }

    protected function getContentsOfCommandMock() : string
    {
        return \print_r(['option' => 'foo', 'o' => 1], true) . \PHP_EOL
            . \print_r(1, true) . \PHP_EOL
            . \print_r(['argument0', 'argument1'], true) . \PHP_EOL
            . \print_r('argument1', true) . \PHP_EOL;
    }

    public function testExec() : void
    {
        $this->console->addCommand(new CommandMock($this->console));
        $this->console->exec('test --option=foo -o argument0 argument1');
        self::assertSame($this->getContentsOfCommandMock(), Stdout::getContents());
    }

    public function testCommandToArgs() : void
    {
        self::assertSame(
            [
                'command',
                '--one=two',
                '--three=four',
                'can I have a "little" more',
            ],
            $this->console::commandToArgs(
                'command --one=two   --three="four" \'can I have a "little" more\''
            )
        );
    }

    public function testCommandAlias() : void
    {
        $command = new CommandMock($this->console);
        $command->setAliases(['t', 'testcmd']);
        $this->console->addCommand($command);
        self::assertSame($command, $this->console->getCommand('t'));
        self::assertSame($command, $this->console->getCommand('testcmd'));
        self::assertTrue($this->console->hasCommand('t'));
    }

    public function testRunCommandByAlias() : void
    {
        $command = new CommandMock($this->console);
        $command->setAliases(['t']);
        $this->console->addCommand($command);
        Stdout::reset();
        $this->console->exec('t --option=foo -o argument0 argument1');
        self::assertSame($this->getContentsOfCommandMock(), Stdout::getContents());
    }

    public function testIndexAliases() : void
    {
        self::assertNotNull($this->console->getCommand('ls'));
        self::assertNotNull($this->console->getCommand('list'));
    }

    public function testAutoHelpLongOption() : void
    {
        $this->console->prepare([
            'file.php',
            'index',
            '--help',
        ]);
        $this->console->run();
        self::assertStringContainsString('Usage', Stdout::getContents());
        self::assertStringContainsString('index', Stdout::getContents());
    }

    public function testAutoHelpShortOption() : void
    {
        $this->console->prepare([
            'file.php',
            'index',
            '-h',
        ]);
        $this->console->run();
        self::assertStringContainsString('Usage', Stdout::getContents());
    }

    public function testAutoHelpWithArgumentsShowsCommandHelp() : void
    {
        $this->console->addCommand(new Commands\Host($this->console));
        Stderr::reset();
        $this->console->prepare([
            'file.php',
            'host',
            '0.0.0.0',
            '--help',
        ]);
        $this->console->run();
        $output = Stdout::getContents();
        self::assertStringContainsString('host', $output);
        self::assertStringNotContainsString(
            'Command not found',
            Stderr::getContents()
        );
    }

    public function testCommandDeclaringHReceivesShortOption() : void
    {
        $this->console->addCommand(new Commands\Host($this->console));
        $this->console->prepare([
            'file.php',
            'host',
            '-h',
            '0.0.0.0',
        ]);
        $this->console->run();
        $output = Stdout::getContents();
        self::assertStringContainsString('host: 1', $output);
        self::assertStringContainsString('host value: 0.0.0.0', $output);
        self::assertStringNotContainsString('Usage', $output);
    }

    public function testAutoHelpUsesRegisteredHelpCommand() : void
    {
        $this->console->addCommand(new class($this->console) extends Command {
            protected string $name = 'help';

            public function run() : void
            {
                CLI::write('custom help called');
            }
        });
        $this->console->prepare([
            'file.php',
            'index',
            '--help',
        ]);
        $this->console->run();
        self::assertStringContainsString(
            'custom help called',
            Stdout::getContents()
        );
    }

    public function testQuietOption() : void
    {
        CLI::setQuiet(false);
        $this->console->prepare([
            'file.php',
            'index',
            '--quiet',
        ]);
        self::assertTrue(CLI::isQuiet());
        self::assertArrayNotHasKey('quiet', $this->console->getOptions());
        CLI::setQuiet(false);
    }

    public function testQuietOptionIsScopedToTheDispatch() : void
    {
        CLI::setQuiet(false);
        $this->console->exec('index --quiet');
        self::assertFalse(CLI::isQuiet());
        $this->console->exec('index');
        self::assertFalse(CLI::isQuiet());
    }

    public function testShortQuietOptionIsScopedToTheDispatch() : void
    {
        CLI::setQuiet(false);
        $this->console->exec('index -q');
        self::assertFalse(CLI::isQuiet());
    }

    public function testNoAnsiOption() : void
    {
        CLI::setAnsi(true);
        $this->console->prepare([
            'file.php',
            'index',
            '--no-ansi',
        ]);
        self::assertFalse(CLI::supportsAnsi());
        self::assertArrayNotHasKey('no-ansi', $this->console->getOptions());
        CLI::setAnsi(true);
    }

    public function testNoAnsiOptionIsScopedToTheDispatch() : void
    {
        CLI::setAnsi(true);
        $this->console->exec('index --no-ansi');
        self::assertTrue(CLI::isAnsi());
    }

    public function testHelpShowsArgumentAndOptionDefinitions() : void
    {
        $command = new CommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'required' => true, 'description' => 'The record id'],
        ]);
        $command->setOptionDefinitions([
            'count' => ['type' => 'int', 'default' => 5, 'description' => 'How many records'],
        ]);
        $this->console->addCommand($command);
        Stdout::reset();
        $this->console->exec('test --help');
        $contents = Stdout::getContents();
        self::assertStringContainsString('Arguments', $contents);
        self::assertStringContainsString('The record id.', $contents);
        self::assertStringContainsString('Options', $contents);
        self::assertStringContainsString('--count', $contents);
        self::assertStringContainsString('required, int', $contents);
        self::assertStringContainsString('default "5"', $contents);
        self::assertStringContainsString('How many records.', $contents);
    }

    public function testHelpFallsBackToLegacyOptionsMap() : void
    {
        $this->console->addCommand(new CommandMock($this->console));
        Stdout::reset();
        $this->console->exec('test --help');
        $contents = Stdout::getContents();
        self::assertStringContainsString('Options', $contents);
        self::assertStringContainsString('foo bar', $contents);
    }

    public function testSuccessfulCommandExitsWithZero() : void
    {
        $this->console->addCommand(new QuitterCommandMock($this->console));
        $this->console->prepare(['file.php', 'quitter', '0']);
        self::assertSame(0, $this->console->run());
        self::assertSame(0, $this->console->getExitCode());
    }

    public function testCommandCanDecideTheExitCode() : void
    {
        $this->console->addCommand(new QuitterCommandMock($this->console));
        $this->console->prepare(['file.php', 'quitter', '2']);
        self::assertSame(2, $this->console->run());
        self::assertSame(2, $this->console->getExitCode());
    }

    public function testUnknownCommandYieldsExitCodeOne() : void
    {
        $this->console->prepare(['file.php', 'nope-nope-nope']);
        self::assertSame(1, $this->console->run());
        self::assertSame(1, $this->console->getExitCode());
    }

    public function testValidationFailureYieldsExitCodeOne() : void
    {
        $command = new CommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'required' => true],
        ]);
        $this->console->addCommand($command);
        $this->console->prepare(['file.php', 'test', 'not-an-int']);
        self::assertSame(1, $this->console->run());
        self::assertSame(1, $this->console->getExitCode());
    }

    public function testHelpForUnknownCommandYieldsExitCodeOne() : void
    {
        $this->console->prepare(['file.php', 'help', 'nope-nope-nope']);
        self::assertSame(1, $this->console->run());
        self::assertSame(1, $this->console->getExitCode());
    }

    public function testThrownExceptionIsReportedOnStderr() : void
    {
        Stderr::reset();
        Stderr::init();
        $this->console->addCommand(new ThrowingCommandMock($this->console));
        $this->console->prepare(['file.php', 'thrower']);
        self::assertSame(7, $this->console->run());
        self::assertStringContainsString('boom', Stderr::getContents());
        Stderr::reset();
    }

    public function testDebugModeRevealsClassAndTrace() : void
    {
        Stderr::reset();
        Stderr::init();
        $this->console->addCommand(new ThrowingCommandMock($this->console));
        $this->console->setDebug(true);
        $this->console->prepare(['file.php', 'thrower']);
        $this->console->run();
        self::assertStringContainsString('RuntimeException', Stderr::getContents());
        self::assertStringContainsString('#0', Stderr::getContents());
        $this->console->setDebug(false);
        Stderr::reset();
    }

    public function testExceptionHandlerBypassesDefaultRendering() : void
    {
        Stderr::reset();
        Stderr::init();
        $caught = null;
        $this->console->setExceptionHandler(static function (\Throwable $exception) use (&$caught) : void {
            $caught = $exception;
        });
        $this->console->addCommand(new ThrowingCommandMock($this->console));
        $this->console->prepare(['file.php', 'thrower']);
        self::assertSame(1, $this->console->run());
        self::assertNotNull($caught);
        self::assertSame('boom', $caught->getMessage());
        self::assertStringNotContainsString('boom', Stderr::getContents());
        $this->console->setExceptionHandler(null);
        Stderr::reset();
    }
}
