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
}
