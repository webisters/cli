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

use JetBrains\PhpStorm\Pure;

/**
 * Class Command.
 *
 * @package cli
 */
abstract class Command
{
    /**
     * Console instance of the current command.
     */
    protected Console $console;
    /**
     * Command name.
     */
    protected string $name;
    /**
     * Command group.
     */
    protected string $group;
    /**
     * Command description.
     */
    protected string $description;
    /**
     * Command aliases.
     *
     * @var array<int,string>
     */
    protected array $aliases = [];
    /**
     * Command usage.
     */
    protected string $usage = 'command [options] -- [arguments]';
    /**
     * Command options.
     *
     * @var array<string,string>
     */
    protected array $options = [];
    /**
     * Argument definitions.
     *
     * @var array<int,array<string,mixed>> Definitions keyed by position, each
     * with optional "name", "type", "required", "default" and "description" keys
     */
    protected array $argumentDefinitions = [];
    /**
     * Option definitions.
     *
     * @var array<string,array<string,mixed>> Definitions keyed by option name,
     * each with optional "type" ("string", "int", "float", "numeric" or
     * "flag"), "required" and "default" keys. Only options declared as
     * "flag" accept being passed without a value
     */
    protected array $optionDefinitions = [];
    /**
     * Tells if command is active.
     */
    protected bool $active = true;
    /**
     * The exit code the command wants the process to use.
     */
    protected int $exitCode = 0;

    /**
     * Command constructor.
     *
     * @param Console|null $console
     */
    public function __construct(?Console $console = null)
    {
        if ($console) {
            $this->console = $console;
        }
    }

    /**
     * Run the command.
     */
    abstract public function run() : void;

    /**
     * Get console instance.
     *
     * @return Console
     */
    public function getConsole() : Console
    {
        return $this->console;
    }

    /**
     * Set console instance.
     *
     * @param Console $console
     *
     * @return static
     */
    public function setConsole(Console $console) : static
    {
        $this->console = $console;
        return $this;
    }

    /**
     * Get command name.
     *
     * @return string
     */
    public function getName() : string
    {
        if (isset($this->name)) {
            return $this->name;
        }
        $name = static::class;
        $pos = \strrpos($name, '\\');
        if ($pos !== false) {
            $name = \substr($name, $pos + 1);
        }
        if (\str_ends_with($name, 'Command')) {
            $name = \substr($name, 0, -7);
        }
        $name = \strtolower($name);
        return $this->name = $name;
    }

    /**
     * Set command name.
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name) : static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get command group.
     *
     * @return string|null
     */
    public function getGroup() : ?string
    {
        return $this->group ?? null;
    }

    /**
     * Set command group.
     *
     * @param string $group
     *
     * @return static
     */
    public function setGroup(string $group) : static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Get command description.
     *
     * @return string
     */
    public function getDescription() : string
    {
        if (isset($this->description)) {
            return $this->description;
        }
        $description = $this->console->getLanguage()->render('cli', 'noDescription');
        return $this->description = $description;
    }

    /**
     * Set command description.
     *
     * @param string $description
     *
     * @return static
     */
    public function setDescription(string $description) : static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get command aliases.
     *
     * @return array<int,string>
     */
    #[Pure]
    public function getAliases() : array
    {
        return $this->aliases;
    }

    /**
     * Set command aliases.
     *
     * @param array<int,string> $aliases
     *
     * @return static
     */
    public function setAliases(array $aliases) : static
    {
        $this->aliases = \array_values($aliases);
        return $this;
    }

    /**
     * Get command usage.
     *
     * @return string
     */
    #[Pure]
    public function getUsage() : string
    {
        return $this->usage;
    }

    /**
     * Set command usage.
     *
     * @param string $usage
     *
     * @return static
     */
    public function setUsage(string $usage) : static
    {
        $this->usage = $usage;
        return $this;
    }

    /**
     * Get command options.
     *
     * @return array<string,string>
     */
    #[Pure]
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * Set command options.
     *
     * @param array<string,string> $options
     *
     * @return static
     */
    public function setOptions(array $options) : static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get argument definitions.
     *
     * @return array<int,array<string,mixed>> Definitions keyed by position,
     * each with optional "type", "required" and "default" keys
     */
    #[Pure]
    public function getArgumentDefinitions() : array
    {
        return $this->argumentDefinitions;
    }

    /**
     * Set argument definitions.
     *
     * @param array<int,array<string,mixed>> $definitions Definitions keyed by
     * position, each with optional "type", "required" and "default" keys
     *
     * @return static
     */
    public function setArgumentDefinitions(array $definitions) : static
    {
        $this->argumentDefinitions = $definitions;
        return $this;
    }

    /**
     * Get option definitions.
     *
     * @return array<string,array<string,mixed>> Definitions keyed by option
     * name, each with optional "type", "required" and "default" keys
     */
    #[Pure]
    public function getOptionDefinitions() : array
    {
        return $this->optionDefinitions;
    }

    /**
     * Set option definitions.
     *
     * @param array<string,array<string,mixed>> $definitions Definitions keyed
     * by option name, each with optional "type", "required" and "default" keys
     *
     * @return static
     */
    public function setOptionDefinitions(array $definitions) : static
    {
        $this->optionDefinitions = $definitions;
        return $this;
    }

    /**
     * Validate parsed arguments and options against the definitions.
     *
     * Supported types are "string", "int", "float" and "numeric". An argument
     * or option marked as required must be present. Values declared with a
     * type are cast when possible and reported as errors when they do not
     * match.
     *
     * @param array<int,string> $arguments The parsed positional arguments
     * @param array<string,bool|string> $options The parsed options
     *
     * @return array<int,string> The validation error messages, empty when valid
     */
    public function validate(array $arguments, array $options) : array
    {
        return \array_merge(
            $this->validateDefinitions($this->argumentDefinitions, $arguments, 'argument'),
            $this->validateDefinitions($this->optionDefinitions, $options, 'option')
        );
    }

    /**
     * Apply definition defaults to the console.
     *
     * Any defined argument or option that was not passed on the command line
     * and declares a "default" key gets its default written back into the
     * Console, so getArgument() and getOption() return the resolved value.
     * Values are stored as strings (or booleans for flags) because the
     * Console accessors are typed that way.
     *
     * @param Console $console The console the command is running on
     */
    public function applyDefaults(Console $console) : void
    {
        $arguments = $console->getArguments();
        foreach ($this->argumentDefinitions as $key => $definition) {
            $default = $definition['default'] ?? null;
            if (!\array_key_exists($key, $arguments) && \is_scalar($default)) {
                $console->setArgument((int) $key, (string) $default);
            }
        }
        $options = $console->getOptions();
        foreach ($this->optionDefinitions as $key => $definition) {
            $default = $definition['default'] ?? null;
            if (\array_key_exists($key, $options) || !\is_scalar($default)) {
                continue;
            }
            $console->setOption((string) $key, \is_bool($default) ? $default : (string) $default);
        }
    }

    /**
     * Validate a set of values against their definitions.
     *
     * @param array<int|string,array<string,mixed>> $definitions
     * @param array<int|string,bool|string> $values
     * @param string $label Either "argument" or "option", used in messages
     *
     * @return array<int,string>
     */
    protected function validateDefinitions(array $definitions, array $values, string $label) : array
    {
        $errors = [];
        $translator = isset($this->console) ? $this->console->getLanguage() : null;
        if ($translator) {
            $label = $translator->render('cli', $label, []);
        }
        foreach ($definitions as $key => $definition) {
            $displayName = $this->definitionLabel($key, $definition);
            $value = $values[$key] ?? null;
            if ($value === null || $value === false) {
                if (!empty($definition['required'])) {
                    $errors[] = $translator
                        ? $translator->render('cli', 'validation.required', [$label, $displayName])
                        : $label . ' "' . $displayName . '" is required.';
                }
                continue;
            }
            $type = $definition['type'] ?? 'string';
            if (!\is_string($type)) {
                $type = 'string';
            }
            if ($type === 'flag') {
                continue;
            }
            if (!\is_string($value) || $type === 'string') {
                if (!\is_string($value)) {
                    // A boolean (true) means the option was passed without a
                    // value, which is only valid for "flag" definitions
                    $errors[] = $translator
                        ? $translator->render('cli', 'validation.type', [$label, $displayName, $type])
                        : $label . ' "' . $displayName . '" must be of type ' . $type . '.';
                }
                continue;
            }
            $valid = match ($type) {
                'int' => (bool) \preg_match('/^-?\d+$/', $value),
                'float' => \is_numeric($value),
                'numeric' => \is_numeric($value),
                default => true,
            };
            if (!$valid) {
                $errors[] = $translator
                    ? $translator->render('cli', 'validation.type', [$label, $displayName, $type])
                    : $label . ' "' . $displayName . '" must be of type ' . $type . '.';
            }
        }
        return $errors;
    }

    /**
     * Resolve the display label of a definition: its "name" when given,
     * otherwise the array key (the argument position or option name).
     *
     * @param int|string $key The definition key
     * @param array<string,mixed> $definition The definition
     *
     * @return string
     */
    #[Pure]
    protected function definitionLabel(int | string $key, array $definition) : string
    {
        $name = $definition['name'] ?? null;
        if (\is_string($name) && $name !== '') {
            return $name;
        }
        return (string) $key;
    }

    /**
     * Get the exit code the command wants the process to use.
     *
     * Returns 0 unless the command called setExitCode() during run().
     *
     * @return int
     */
    #[Pure]
    public function getExitCode() : int
    {
        return $this->exitCode;
    }

    /**
     * Set the exit code the process should use after this command runs.
     *
     * Lets a command fail, or succeed with a specific code, without writing
     * to STDERR or terminating the process itself.
     *
     * @param int $code The exit code, 0 means success
     *
     * @return static
     */
    public function setExitCode(int $code) : static
    {
        $this->exitCode = $code;
        return $this;
    }

    /**
     * Tells if the command is active.
     *
     * @return bool
     */
    #[Pure]
    public function isActive() : bool
    {
        return $this->active;
    }

    /**
     * Activate the command.
     *
     * @return static
     */
    public function activate() : static
    {
        $this->active = true;
        return $this;
    }

    /**
     * Deactivate the command.
     *
     * @return static
     */
    public function deactivate() : static
    {
        $this->active = false;
        return $this;
    }
}
