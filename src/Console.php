<?php declare(strict_types=1);
/*
 * This file is part of Webisters CLI Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\CLI;

use Framework\CLI\Commands\About;
use Framework\CLI\Commands\Help;
use Framework\CLI\Commands\Index;
use Framework\CLI\Styles\ForegroundColor;
use Framework\Language\Language;
use JetBrains\PhpStorm\Pure;

/**
 * Class Console.
 *
 * @package cli
 */
class Console
{
    /**
     * List of commands.
     *
     * @var array<string,Command> The command name as key and the object as value
     */
    protected array $commands = [];
    /**
     * The current command name.
     */
    protected string $command = '';
    /**
     * Input options.
     *
     * @var array<string,bool|string> The option value as string or TRUE if it
     * was passed without a value
     */
    protected array $options = [];
    /**
     * Input arguments.
     *
     * @var array<int,string>
     */
    protected array $arguments = [];
    /**
     * The input tokens left after the script name and the command name were
     * taken out, kept so the options can be parsed again once the command,
     * and with it the option definitions, is known.
     *
     * @var array<int,string>
     */
    protected array $tokens = [];
    /**
     * The Language instance.
     */
    protected Language $language;
    /**
     * Quiet state captured in prepare() before the console wide flags were
     * applied, restored when the dispatch finishes.
     */
    protected bool $previousQuiet = false;
    /**
     * ANSI state captured in prepare() before the console wide flags were
     * applied, restored when the dispatch finishes.
     */
    protected bool $previousAnsi = true;
    /**
     * The exit code reported by the last dispatched command, or 1 when the
     * command was not found or failed validation.
     */
    protected int $exitCode = 0;
    /**
     * When true, escaped exceptions are rendered with the class name and
     * a stack trace instead of just the message.
     */
    protected bool $debug = false;
    /**
     * Optional application supplied handler for uncaught command exceptions.
     *
     * @var callable(\Throwable):void|null
     */
    protected $exceptionHandler;

    /**
     * Console constructor.
     *
     * @param Language|null $language
     */
    public function __construct(?Language $language = null)
    {
        if ($language) {
            $this->setLanguage($language);
        }
        global $argv;
        // @phpstan-ignore-next-line nullCoalesce.variable
        $this->prepare($argv ?? []);
        $this->setDefaultCommands();
    }

    protected function setDefaultCommands() : static
    {
        if ($this->getCommand('index') === null) {
            $this->addCommand(new Index($this));
        }
        if ($this->getCommand('help') === null) {
            $this->addCommand(new Help($this));
        }
        if ($this->getCommand('about') === null) {
            $this->addCommand(new About($this));
        }
        return $this;
    }

    /**
     * Get all CLI options.
     *
     * @return array<string,bool|string>
     */
    #[Pure]
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * Get a specific option or null.
     *
     * @param string $option
     *
     * @return bool|string|null The option value as string, TRUE if it
     * was passed without a value or NULL if the option was not set
     */
    #[Pure]
    public function getOption(string $option) : bool | string | null
    {
        return $this->options[$option] ?? null;
    }

    /**
     * Get all arguments.
     *
     * @return array<int,string>
     */
    #[Pure]
    public function getArguments() : array
    {
        return $this->arguments;
    }

    /**
     * Get a specific argument or null.
     *
     * @param int $position Argument position, starting from zero
     *
     * @return string|null The argument value or null if it was not set
     */
    #[Pure]
    public function getArgument(int $position) : ?string
    {
        return $this->arguments[$position] ?? null;
    }

    /**
     * Set an argument value.
     *
     * Used by Command::applyDefaults() to fill in definition defaults.
     *
     * @param int $position Argument position, starting from zero
     * @param string $value The argument value
     *
     * @return static
     */
    public function setArgument(int $position, string $value) : static
    {
        $this->arguments[$position] = $value;
        return $this;
    }

    /**
     * Set an option value.
     *
     * Used by Command::applyDefaults() to fill in definition defaults.
     *
     * @param string $name The option name
     * @param bool|string $value The option value
     *
     * @return static
     */
    public function setOption(string $name, bool | string $value) : static
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * Set the Language instance.
     *
     * @param Language|null $language
     *
     * @return static
     */
    public function setLanguage(?Language $language = null) : static
    {
        $this->language = $language ?? new Language();
        $this->language->addDirectory(CLI::path(__DIR__, 'Languages'));
        return $this;
    }

    /**
     * Get the Language instance.
     *
     * @return Language
     */
    public function getLanguage() : Language
    {
        if (!isset($this->language)) {
            $this->setLanguage();
        }
        return $this->language;
    }

    /**
     * Add a command to the console.
     *
     * @param Command|class-string<Command> $command A Command instance or the class FQN
     *
     * @return static
     */
    public function addCommand(Command | string $command) : static
    {
        if (\is_string($command)) {
            $command = new $command();
        }
        $command->setConsole($this);
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    /**
     * Add many commands to the console.
     *
     * @param array<Command|class-string<Command>> $commands A list of Command
     * instances or the classes FQN
     *
     * @return static
     */
    public function addCommands(array $commands) : static
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }
        return $this;
    }

    /**
     * Get an active command.
     *
     * @param string $name Command name
     *
     * @return Command|null The Command on success or null if not found
     */
    public function getCommand(string $name) : ?Command
    {
        if (isset($this->commands[$name]) && $this->commands[$name]->isActive()) {
            return $this->commands[$name];
        }
        foreach ($this->commands as $command) {
            if (\in_array($name, $command->getAliases(), true) && $command->isActive()) {
                return $command;
            }
        }
        return null;
    }

    /**
     * Get a list of active commands.
     *
     * @return array<string,Command>
     */
    public function getCommands() : array
    {
        $commands = $this->commands;
        foreach ($commands as $name => $command) {
            if (!$command->isActive()) {
                unset($commands[$name]);
            }
        }
        \ksort($commands);
        return $commands;
    }

    /**
     * Remove a command.
     *
     * @param string $name Command name
     *
     * @return static
     */
    public function removeCommand(string $name) : static
    {
        unset($this->commands[$name]);
        return $this;
    }

    /**
     * Remove commands.
     *
     * @param array<string> $names Command names
     *
     * @return static
     */
    public function removeCommands(array $names) : static
    {
        foreach ($names as $name) {
            $this->removeCommand($name);
        }
        return $this;
    }

    /**
     * Tells if it has a command.
     *
     * @param string $name Command name
     *
     * @return bool
     */
    public function hasCommand(string $name) : bool
    {
        return $this->getCommand($name) !== null;
    }

    /**
     * Get the current command name.
     *
     * @return string
     */
    #[Pure]
    public function getCommandName() : string
    {
        return $this->command;
    }

    /**
     * Run the Console and return the resulting exit code.
     *
     * The code comes from the dispatched Command via setExitCode(), or is 1
     * when the command was not found or failed validation. Entry points can
     * forward it to the process with exit($console->run()).
     *
     * @return int The process exit code, 0 means success
     */
    public function run() : int
    {
        try {
            return $this->dispatch();
        } finally {
            CLI::setQuiet($this->previousQuiet);
            CLI::setAnsi($this->previousAnsi);
        }
    }

    /**
     * Get the exit code reported by the last dispatched command.
     *
     * @return int
     */
    #[Pure]
    public function getExitCode() : int
    {
        return $this->exitCode;
    }

    /**
     * Enable or disable debug rendering of uncaught command exceptions.
     *
     * When enabled, escaped exceptions are rendered with their class name and
     * a full stack trace instead of just the message.
     *
     * @param bool $debug True to enable debug rendering
     *
     * @return static
     */
    public function setDebug(bool $debug) : static
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Tell whether debug rendering of uncaught exceptions is enabled.
     *
     * @return bool
     */
    #[Pure]
    public function isDebug() : bool
    {
        return $this->debug;
    }

    /**
     * Register an application handler for uncaught command exceptions.
     *
     * The handler receives the Throwable and is responsible for any logging
     * or rendering the application needs. When set, it runs before the
     * library's own error output, which is then skipped.
     *
     * @param callable(\Throwable):void|null $handler The handler or null to clear it
     *
     * @return static
     */
    public function setExceptionHandler(?callable $handler) : static
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * Dispatch the current command and return its exit code.
     *
     * @return int The process exit code, 0 means success
     */
    protected function dispatch() : int
    {
        $this->exitCode = 0;
        if ($this->command === '') {
            $this->command = 'index';
        }
        if ($this->isHelpRequested()) {
            $help = $this->getCommand('help') ?? new Help($this);
            $help->run();
            $this->exitCode = $help->getExitCode();
            return $this->exitCode;
        }
        $command = $this->getCommand($this->command);
        if ($command === null) {
            $this->commandNotFound($this->command);
            return $this->exitCode;
        }
        $this->reparseWithOptions($command);
        $errors = $command->validate($this->arguments, $this->options);
        if ($errors !== []) {
            $this->validationFailed($errors);
            return $this->exitCode;
        }
        $command->applyDefaults($this);
        try {
            $command->run();
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
        $this->exitCode = $command->getExitCode();
        return $this->exitCode;
    }

    /**
     * Handle an exception that escaped the dispatched command.
     *
     * If an application exception handler is registered it is invoked and the
     * library renders nothing. Otherwise the message is printed in red on
     * STDERR, with the class name and stack trace added in debug mode, and a
     * non zero exit code is reported.
     *
     * @param \Throwable $exception The uncaught exception
     *
     * @return int The process exit code
     */
    protected function handleException(\Throwable $exception) : int
    {
        if ($this->exceptionHandler !== null) {
            ($this->exceptionHandler)($exception);
            $this->exitCode = 1;
            return $this->exitCode;
        }
        $exitCode = (int) $exception->getCode();
        if ($exitCode < 1 || $exitCode > 254) {
            $exitCode = 1;
        }
        $message = $exception->getMessage();
        if ($this->debug) {
            $message = \get_class($exception) . ': ' . $message
                . \PHP_EOL . $exception->getTraceAsString();
        }
        CLI::error(
            CLI::style($message, ForegroundColor::brightRed),
            \defined('TESTING') ? null : $exitCode
        );
        $this->exitCode = $exitCode;
        return $this->exitCode;
    }

    /**
     * Report argument or option validation errors for the requested command.
     *
     * @param array<int,string> $errors The validation error messages
     */
    protected function validationFailed(array $errors) : void
    {
        $this->exitCode = 1;
        $message = \implode(\PHP_EOL, $errors);
        CLI::error(
            CLI::style($message, ForegroundColor::brightRed),
            \defined('TESTING') ? null : 1
        );
    }

    /**
     * Tells if the user asked for help via the -h or --help option.
     *
     * @return bool
     */
    protected function isHelpRequested() : bool
    {
        $command = $this->getCommand($this->command);
        if ($command !== null) {
            $declared = static::declaredOptionNames($command);
            if ($this->getOption('help') === true
                && !\in_array('help', $declared, true)) {
                return true;
            }
            if ($this->getOption('h') === true
                && !\in_array('h', $declared, true)) {
                return true;
            }
            return false;
        }
        return $this->getOption('help') === true || $this->getOption('h') === true;
    }

    /**
     * Handle an unknown command by reporting it and suggesting the closest
     * registered command when there is a close match.
     *
     * @param string $command The unknown command name
     */
    protected function commandNotFound(string $command) : void
    {
        $this->exitCode = 1;
        $message = $this->getLanguage()->render('cli', 'commandNotFound', [$command]);
        $suggestion = $this->suggestCommand($command);
        if ($suggestion !== null && $suggestion !== $command) {
            $message .= \PHP_EOL
                . $this->getLanguage()->render('cli', 'didYouMean', [$suggestion]);
        }
        CLI::error(
            CLI::style($message, ForegroundColor::brightRed),
            \defined('TESTING') ? null : 1
        );
    }

    /**
     * Suggest the closest active command name or alias to a given input using
     * the Levenshtein distance, or null when no match is close enough.
     *
     * @param string $command The unknown command name
     *
     * @return string|null The closest command name, alias or null
     */
    #[Pure]
    protected function suggestCommand(string $command) : ?string
    {
        if ($command === '') {
            return null;
        }
        $candidates = [];
        foreach (static::getCommands() as $name => $activeCommand) {
            $candidates[] = $name;
            foreach ($activeCommand->getAliases() as $alias) {
                $candidates[] = $alias;
            }
        }
        $threshold = (int) \strlen($command) / 4 + 1;
        $best = null;
        $bestDistance = $threshold + 1;
        foreach ($candidates as $candidate) {
            $distance = \levenshtein($command, $candidate);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }
        if ($bestDistance > $threshold) {
            return null;
        }
        return $best;
    }

    public function exec(string $command) : int
    {
        $argumentValues = static::commandToArgs($command);
        \array_unshift($argumentValues, 'removed');
        $this->prepare($argumentValues);
        return $this->run();
    }

    protected function reset() : void
    {
        $this->command = '';
        $this->options = [];
        $this->arguments = [];
        $this->tokens = [];
    }

    /**
     * Prepare information of the command line.
     *
     * [options] [arguments] [options]
     * [options] -- [arguments]
     * [command]
     * [command] [options] [arguments] [options]
     * [command] [options] -- [arguments]
     * Short option: -l, -la === l = true, a = true
     * Long option: --list, --all=vertical === list = true, all = vertical
     * Options always receive values with an equal sign:
     * --foo=bar or --f=bar - "foo" and "f" are bar
     * -foo=bar or -f=bar - all characters are true (f, o, =, b, a, r)
     * An option the command declares in its option definitions with a type
     * other than "flag" also receives the next token as value, so -o value
     * and --opt value work. Options without a definition stay true.
     * A token that is a negative number, like -5, is an argument.
     * After -- all values are arguments, also if is prefixed with -
     * Without --, arguments and options can be mixed: -ls foo -x abc --a=e.
     *
     * @param array<int,string> $argumentValues
     */
    protected function prepare(array $argumentValues) : void
    {
        $this->reset();
        unset($argumentValues[0]);
        if (isset($argumentValues[1]) && $argumentValues[1] !== '' && $argumentValues[1][0] !== '-') {
            $this->command = $argumentValues[1];
            unset($argumentValues[1]);
        }
        $this->tokens = \array_values($argumentValues);
        $this->parseTokens();
        $this->previousQuiet = CLI::isQuiet();
        $this->previousAnsi = CLI::isAnsi();
        $this->applyGlobalOptions();
    }

    /**
     * Parse the prepared tokens into options and arguments.
     *
     * @param array<int,string> $valueOptions Names of the options that take
     * the next token as their value
     */
    protected function parseTokens(array $valueOptions = []) : void
    {
        $this->options = [];
        $this->arguments = [];
        $endOptions = false;
        $total = \count($this->tokens);
        for ($index = 0; $index < $total; $index++) {
            $value = $this->tokens[$index];
            if ($endOptions === false && $value === '--') {
                $endOptions = true;
                continue;
            }
            if ($endOptions === false && static::isOptionToken($value)) {
                if (isset($value[1]) && $value[1] === '-') {
                    $option = \substr($value, 2);
                    if (\str_contains($option, '=')) {
                        [$option, $value] = \explode('=', $option, 2);
                        $this->options[$option] = $value;
                        continue;
                    }
                    if (\in_array($option, $valueOptions, true)
                        && $this->hasValueAt($index + 1)) {
                        $index++;
                        $this->options[$option] = $this->tokens[$index];
                        continue;
                    }
                    $this->options[$option] = true;
                    continue;
                }
                $items = \str_split(\substr($value, 1));
                $lastItem = \array_key_last($items);
                foreach ($items as $position => $item) {
                    if ($position === $lastItem
                        && \in_array($item, $valueOptions, true)
                        && $this->hasValueAt($index + 1)) {
                        $index++;
                        $this->options[$item] = $this->tokens[$index];
                        continue;
                    }
                    $this->options[$item] = true;
                }
                continue;
            }
            //$endOptions = true;
            $this->arguments[] = $value;
        }
    }

    /**
     * Parse the options again now that the command, and with it the option
     * definitions telling which options take a value, is known.
     *
     * @param Command $command The command about to be dispatched
     */
    protected function reparseWithOptions(Command $command) : void
    {
        $valueOptions = static::valueOptionNames($command);
        if ($valueOptions === []) {
            return;
        }
        $this->parseTokens($valueOptions);
        $this->applyGlobalOptions();
    }

    /**
     * Tells if the token at a given position can be taken as an option value.
     *
     * @param int $index The token position
     *
     * @return bool False when there is no token left or the token is itself
     * an option or the end of options marker
     */
    #[Pure]
    protected function hasValueAt(int $index) : bool
    {
        return isset($this->tokens[$index])
            && $this->tokens[$index] !== '--'
            && !static::isOptionToken($this->tokens[$index]);
    }

    /**
     * Apply the console wide options like quiet mode and disabling ANSI colors.
     */
    protected function applyGlobalOptions() : void
    {
        if (isset($this->options['no-ansi'])) {
            CLI::setAnsi(false);
            unset($this->options['no-ansi']);
        }
        if ((isset($this->options['quiet']) && $this->options['quiet'] === true)
            || (isset($this->options['q']) && $this->options['q'] === true)) {
            CLI::setQuiet(true);
            unset($this->options['quiet'], $this->options['q']);
        }
    }

    /**
     * Tells if a token is an option and not an argument.
     *
     * A negative number is an argument, so -5 can be passed without the --
     * end of options marker.
     *
     * @param string $token The input token
     *
     * @return bool
     */
    #[Pure]
    protected static function isOptionToken(string $token) : bool
    {
        return $token !== '' && $token[0] === '-' && !\is_numeric($token);
    }

    /**
     * List the option names a command declares with a type other than "flag",
     * which are the ones able to take the next token as their value.
     *
     * @param Command $command The command to inspect
     *
     * @return array<int,string> Names without their leading dashes
     */
    #[Pure]
    protected static function valueOptionNames(Command $command) : array
    {
        $names = [];
        foreach ($command->getOptionDefinitions() as $key => $definition) {
            if (($definition['type'] ?? 'string') === 'flag') {
                continue;
            }
            $names[] = \ltrim(\trim((string) $key), '-');
        }
        return $names;
    }

    /**
     * List the short and long option names a command declares for itself.
     *
     * @param Command $command The command to inspect
     *
     * @return array<int,string> Names without their leading dashes
     */
    #[Pure]
    protected static function declaredOptionNames(Command $command) : array
    {
        $names = [];
        foreach (\array_keys($command->getOptions()) as $key) {
            foreach (\explode(',', (string) $key) as $part) {
                $names[] = \ltrim(\trim($part), '-');
            }
        }
        foreach (\array_keys($command->getOptionDefinitions()) as $key) {
            $names[] = \ltrim(\trim((string) $key), '-');
        }
        return $names;
    }

    /**
     * @param string $command
     *
     * @see https://someguyjeremy.com/2017/07/adventures-in-parsing-strings-to-argv-in-php.html
     *
     * @return array<int,string>
     */
    #[Pure]
    public static function commandToArgs(string $command) : array
    {
        $charCount = \strlen($command);
        $argv = [];
        $arg = '';
        $inDQuote = false;
        $inSQuote = false;
        for ($i = 0; $i < $charCount; $i++) {
            $char = $command[$i];
            if ($char === ' ' && !$inDQuote && !$inSQuote) {
                if ($arg !== '') {
                    $argv[] = $arg;
                }
                $arg = '';
                continue;
            }
            if ($inSQuote && $char === "'") {
                $inSQuote = false;
                continue;
            }
            if ($inDQuote && $char === '"') {
                $inDQuote = false;
                continue;
            }
            if ($char === '"' && !$inSQuote) {
                $inDQuote = true;
                continue;
            }
            if ($char === "'" && !$inDQuote) {
                $inSQuote = true;
                continue;
            }
            $arg .= $char;
        }
        $argv[] = $arg;
        return $argv;
    }
}
