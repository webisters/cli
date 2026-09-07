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
use PHPUnit\Framework\TestCase;

/**
 * Command mock used by OptionValueTest.
 */
class OptionValueCommandMock extends Command
{
    protected string $name = 'serve';

    public function run() : void
    {
        CLI::write('ran');
    }
}

final class OptionValueTest extends TestCase
{
    protected ConsoleMock $console;

    protected function setUp() : void
    {
        Stdout::init();
        Stderr::init();
        $this->console = new ConsoleMock();
    }

    protected function tearDown() : void
    {
        Stdout::reset();
        Stderr::reset();
    }

    /**
     * Register the "serve" command with the given option definitions.
     *
     * @param array<string,array<string,mixed>> $definitions
     *
     * @return OptionValueCommandMock
     */
    protected function serveCommand(array $definitions) : OptionValueCommandMock
    {
        $command = new OptionValueCommandMock($this->console);
        $command->setOptionDefinitions($definitions);
        $this->console->addCommand($command);
        return $command;
    }

    public function testShortOptionTakesTheNextTokenAsValue() : void
    {
        $this->serveCommand([
            'h' => ['type' => 'string'],
        ]);
        $this->console->exec('serve -h 0.0.0.0');
        self::assertSame('0.0.0.0', $this->console->getOption('h'));
        self::assertSame([], $this->console->getArguments());
    }

    public function testLongOptionTakesTheNextTokenAsValue() : void
    {
        $this->serveCommand([
            'port' => ['type' => 'int'],
        ]);
        $this->console->exec('serve --port 8080');
        self::assertSame('8080', $this->console->getOption('port'));
        self::assertSame([], $this->console->getArguments());
    }

    public function testUndefinedOptionKeepsTheBooleanBehaviour() : void
    {
        $this->serveCommand([]);
        $this->console->exec('serve -x 0.0.0.0');
        self::assertTrue($this->console->getOption('x'));
        self::assertSame([
            '0.0.0.0',
        ], $this->console->getArguments());
    }

    public function testFlagOptionDoesNotTakeTheNextToken() : void
    {
        $this->serveCommand([
            'all' => ['type' => 'flag'],
        ]);
        $this->console->exec('serve --all now');
        self::assertTrue($this->console->getOption('all'));
        self::assertSame([
            'now',
        ], $this->console->getArguments());
    }

    public function testValueOptionDoesNotTakeAnotherOption() : void
    {
        $this->serveCommand([
            'port' => ['type' => 'int'],
            'verbose' => ['type' => 'flag'],
        ]);
        $this->console->exec('serve --port --verbose');
        self::assertTrue($this->console->getOption('port'));
        self::assertTrue($this->console->getOption('verbose'));
    }

    public function testValueOptionDoesNotTakeTheEndOfOptionsMarker() : void
    {
        $this->serveCommand([
            'port' => ['type' => 'int'],
        ]);
        $this->console->exec('serve --port -- 8080');
        self::assertTrue($this->console->getOption('port'));
        self::assertSame([
            '8080',
        ], $this->console->getArguments());
    }

    public function testShortGroupTakesTheNextTokenForItsLastOption() : void
    {
        $this->serveCommand([
            'v' => ['type' => 'flag'],
            'f' => ['type' => 'string'],
        ]);
        $this->console->exec('serve -vf config.php');
        self::assertTrue($this->console->getOption('v'));
        self::assertSame('config.php', $this->console->getOption('f'));
        self::assertSame([], $this->console->getArguments());
    }

    public function testEqualSignKeepsPrecedenceOverTheNextToken() : void
    {
        $this->serveCommand([
            'port' => ['type' => 'int'],
        ]);
        $this->console->exec('serve --port=8080 public');
        self::assertSame('8080', $this->console->getOption('port'));
        self::assertSame([
            'public',
        ], $this->console->getArguments());
    }

    public function testArgumentsKeepTheirPositionsAfterAValueIsTaken() : void
    {
        $command = $this->serveCommand([
            'port' => ['type' => 'int'],
        ]);
        $command->setArgumentDefinitions([
            0 => ['type' => 'string', 'required' => true],
        ]);
        $this->console->exec('serve --port 8080 public');
        self::assertSame('8080', $this->console->getOption('port'));
        self::assertSame([
            'public',
        ], $this->console->getArguments());
        self::assertStringContainsString('ran', Stdout::getContents());
    }

    public function testGlobalOptionsAreStillRemovedAfterTheReparse() : void
    {
        $this->serveCommand([
            'port' => ['type' => 'int'],
        ]);
        $this->console->exec('serve --port 8080 --quiet');
        self::assertArrayNotHasKey('quiet', $this->console->getOptions());
    }

    public function testNegativeIntegerIsAnArgument() : void
    {
        $this->console->prepare([
            'file.php',
            'calc',
            '-5',
        ]);
        self::assertSame([], $this->console->getOptions());
        self::assertSame([
            '-5',
        ], $this->console->getArguments());
    }

    public function testNegativeFloatIsAnArgument() : void
    {
        $this->console->prepare([
            'file.php',
            'calc',
            '-1.5',
        ]);
        self::assertSame([], $this->console->getOptions());
        self::assertSame([
            '-1.5',
        ], $this->console->getArguments());
    }

    public function testNegativeNumberIsTakenAsAnOptionValue() : void
    {
        $this->serveCommand([
            'offset' => ['type' => 'int'],
        ]);
        $this->console->exec('serve --offset -5');
        self::assertSame('-5', $this->console->getOption('offset'));
        self::assertSame([], $this->console->getArguments());
    }
}
