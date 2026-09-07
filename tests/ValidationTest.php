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

/**
 * Validated command mock used by ValidationTest.
 */
class ValidatedCommandMock extends Command
{
    protected string $name = 'validated';

    public function run() : void
    {
        CLI::write('ran');
    }
}

final class ValidationTest extends TestCase
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

    public function testValidInputRunsTheCommand() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'required' => true],
        ]);
        $command->setOptionDefinitions([
            'count' => ['type' => 'int'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated 42 --count=5');
        self::assertStringContainsString('ran', Stdout::getContents());
    }

    public function testMissingRequiredArgumentReportsAnError() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'required' => true],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated');
        self::assertStringContainsString('argument "0" is required', Stderr::getContents());
        self::assertStringNotContainsString('ran', Stdout::getContents());
    }

    public function testInvalidArgumentTypeReportsAnError() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated abc');
        self::assertStringContainsString('argument "0" must be of type int', Stderr::getContents());
    }

    public function testMissingRequiredOptionReportsAnError() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setOptionDefinitions([
            'count' => ['type' => 'int', 'required' => true],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated 42');
        self::assertStringContainsString('option "count" is required', Stderr::getContents());
    }

    public function testTypedOptionWithoutValueReportsAnError() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setOptionDefinitions([
            'limit' => ['type' => 'int'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated 5 --limit');
        self::assertStringContainsString('option "limit" must be of type int', Stderr::getContents());
        self::assertStringNotContainsString('ran', Stdout::getContents());
    }

    public function testFlagOptionAllowsBeingPassedWithoutValue() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setOptionDefinitions([
            'all' => ['type' => 'flag'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated 5 --all');
        self::assertStringContainsString('ran', Stdout::getContents());
        self::assertTrue($this->console->getOption('all'));
    }

    public function testTypedOptionWithValueStillValidates() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setOptionDefinitions([
            'limit' => ['type' => 'int'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated 5 --limit=abc');
        self::assertStringContainsString('option "limit" must be of type int', Stderr::getContents());
    }

    public function testValidationErrorsAreTranslatedToSpanish() : void
    {
        $console = new ConsoleMock(new Language('es'));
        $command = new ValidatedCommandMock($console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'required' => true],
        ]);
        $command->setOptionDefinitions([
            'count' => ['type' => 'int', 'required' => true],
        ]);
        $console->addCommand($command);
        $console->exec('validated');
        self::assertStringContainsString('argumento "0" es obligatorio.', Stderr::getContents());
        self::assertStringContainsString('opción "count" es obligatorio.', Stderr::getContents());
        $console->exec('validated abc');
        self::assertStringContainsString('argumento "0" debe ser del tipo int.', Stderr::getContents());
    }

    public function testValidationErrorsAreTranslatedToBrazilianPortuguese() : void
    {
        $console = new ConsoleMock(new Language('pt-br'));
        $command = new ValidatedCommandMock($console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'required' => true],
        ]);
        $command->setOptionDefinitions([
            'count' => ['type' => 'int', 'required' => true],
        ]);
        $console->addCommand($command);
        $console->exec('validated');
        self::assertStringContainsString('argumento "0" é obrigatório.', Stderr::getContents());
        self::assertStringContainsString('opção "count" é obrigatório.', Stderr::getContents());
        $console->exec('validated abc');
        self::assertStringContainsString('argumento "0" deve ser do tipo int.', Stderr::getContents());
    }

    public function testGettersReturnTheDefinitions() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([0 => ['type' => 'int']]);
        $command->setOptionDefinitions(['count' => ['type' => 'int']]);
        self::assertSame([0 => ['type' => 'int']], $command->getArgumentDefinitions());
        self::assertSame(['count' => ['type' => 'int']], $command->getOptionDefinitions());
    }

    public function testDefaultArgumentIsAppliedWhenMissing() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'default' => '7'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated');
        self::assertSame('7', $this->console->getArgument(0));
        self::assertStringContainsString('ran', Stdout::getContents());
    }

    public function testDefaultOptionIsAppliedWhenMissing() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setOptionDefinitions([
            'count' => ['type' => 'int', 'default' => '3'],
            'verbose' => ['type' => 'flag', 'default' => true],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated');
        self::assertSame('3', $this->console->getOption('count'));
        self::assertTrue($this->console->getOption('verbose'));
        self::assertStringContainsString('ran', Stdout::getContents());
    }

    public function testPassedValuesAreNotOverriddenByDefaults() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'default' => '7'],
        ]);
        $command->setOptionDefinitions([
            'count' => ['type' => 'int', 'default' => '3'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated 42 --count=5');
        self::assertSame('42', $this->console->getArgument(0));
        self::assertSame('5', $this->console->getOption('count'));
    }

    public function testNamedArgumentIsUsedInErrorMessages() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['name' => 'environment', 'type' => 'int', 'required' => true],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated');
        self::assertStringContainsString('argument "environment" is required', Stderr::getContents());
        self::assertStringNotContainsString('"0"', Stderr::getContents());
    }

    public function testNamedArgumentTypeErrorsUseTheName() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['name' => 'retries', 'type' => 'int'],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated abc');
        self::assertStringContainsString('argument "retries" must be of type int', Stderr::getContents());
    }

    public function testNamedOptionIsUsedInErrorMessages() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setOptionDefinitions([
            'count' => ['name' => 'attempts', 'type' => 'int', 'required' => true],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated 42');
        self::assertStringContainsString('option "attempts" is required', Stderr::getContents());
    }

    public function testNamedArgumentInTranslatedErrors() : void
    {
        $console = new ConsoleMock(new Language('pt-br'));
        $command = new ValidatedCommandMock($console);
        $command->setArgumentDefinitions([
            0 => ['name' => 'ambiente', 'type' => 'int', 'required' => true],
        ]);
        $console->addCommand($command);
        $console->exec('validated');
        self::assertStringContainsString('argumento "ambiente" é obrigatório.', Stderr::getContents());
    }

    public function testMissingNameFallsBackToTheKey() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['type' => 'int', 'required' => true],
        ]);
        $this->console->addCommand($command);
        $this->console->exec('validated');
        self::assertStringContainsString('argument "0" is required', Stderr::getContents());
    }

    public function testApplyDefaultsDirectlyFillsTheConsole() : void
    {
        $command = new ValidatedCommandMock($this->console);
        $command->setArgumentDefinitions([
            0 => ['default' => 'fallback'],
        ]);
        $command->setOptionDefinitions([
            'name' => ['default' => 'cli'],
        ]);
        $command->applyDefaults($this->console);
        self::assertSame('fallback', $this->console->getArgument(0));
        self::assertSame('cli', $this->console->getOption('name'));
    }
}
