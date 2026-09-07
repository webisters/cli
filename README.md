# Webisters CLI

[![CI](https://github.com/webisters/cli/actions/workflows/ci.yml/badge.svg)](https://github.com/webisters/cli/actions/workflows/ci.yml)

Webisters CLI Library This library is designed for reuse in Composer-based PHP applications.

## What It Provides

A lightweight PHP library for building command line applications. It has three core components:

### `Framework\CLI\CLI`
A static toolkit for terminal output and input:
- `write()`, `style()`, `success()`, `info()`, `error()`, `box()`, `newLine()` for formatted output with optional `Framework\CLI\Styles\ForegroundColor`, `BackgroundColor` and `Format` styling
- `progress()`, `spinner()`, `liveLine()` for live terminal feedback
- `prompt()`, `getInput()`, `secret()` for reading user input
- `table()` for rendering tabular data
- `getWidth()`, `wrap()`, `strlen()`, `clear()`, `beep()` and terminal helpers
- ANSI control with `setAnsi()` and quiet mode with `setQuiet()`
- Signal handling with `onSignal()`, `onSigint()` and `restoreSignal()` when pcntl is available

### `Framework\CLI\Command`
The abstract base class for every console command. Extend it and implement `run()`:
- `$name`, `getDescription()`, `setGroup()`, `setUsage()` and `setAliases()` describe the command and its help output
- `getOptions()` defines the options a command accepts
- `activate()` / `deactivate()` control availability

### `Framework\CLI\Console`
Discovers, registers and runs commands:
- `addCommand()` / `addCommands()` accept Command instances or class names
- `run()` parses argv, matches the requested command (including aliases) and dispatches it
- Unknown commands print an error and exit 1, suggesting the closest matching command name when there is a close match
- `getArgument()`, `getArguments()`, `getOption()` and `getOptions()` expose the parsed command line
- `exec()` re-parses a command string and then calls `run()`

## Output and Color Helpers

All output helpers live on the static `Framework\CLI\CLI` class and write to STDOUT. Colors are emitted only when the terminal supports ANSI; use `CLI::setAnsi(false)` to force plain output.

### Writing text

```php
use Framework\CLI\CLI;
use Framework\CLI\Styles\BackgroundColor;
use Framework\CLI\Styles\ForegroundColor;
use Framework\CLI\Styles\Format;

CLI::write('Plain text');
CLI::write('Colored text', ForegroundColor::green);
CLI::write('On a red background', null, BackgroundColor::red);
CLI::write('Wrapped to 40 columns', null, null, 40);

// Full control with style(): color, background and formats
CLI::write(CLI::style('Warning!', 'yellow', null, [Format::bold]));
```

Colors and formats can be passed as enum cases or as plain strings, for example `'red'`, `'bright_cyan'`, `'underline'`.

### Convenience shortcuts

```php
CLI::success('Task completed');  // green
CLI::info('Just so you know');   // cyan
CLI::error('Something broke');   // red, then exits with code 1
```

### Reading input

```php
// prompt() prints the question and reads a line; options are shown as hints
// and the first option is used as the default when the user presses Enter
$answer  = CLI::prompt('Continue?', ['y', 'n']);
$token   = CLI::secret('Token: ');               // hidden input

// getInput() reads a line without printing anything. The $prepend argument
// is used internally for backslash line continuation and is prefixed to the
// returned value, not displayed.
$line = CLI::getInput();
```

### Live output

```php
foreach ($items as $i => $item) {
    // process $item ...
    CLI::progress($i + 1, \count($items), 'Importing');
}
CLI::spinner(); // spin one frame while waiting
CLI::newLine();
```

## Creating a Custom Command

1. Create a command by extending `Framework\CLI\Command` and implementing `run()`:

```php
<?php

use Framework\CLI\CLI;
use Framework\CLI\Command;

class GreetCommand extends Command
{
    protected string $name = 'greet';

    public function getDescription() : string
    {
        return 'Greets the user.';
    }

    public function getOptions() : array
    {
        return ['-s' => 'Shout the greeting.']; // options the command accepts
    }

    public function run() : void
    {
        $name = $this->getConsole()->getArgument(0) ?? 'world';
        $message = "Hello, {$name}!";

        if ($this->getConsole()->getOption('s')) {
            $message = \strtoupper($message);
        }

        CLI::write($message);
    }
}
```

2. Register the command with a `Console` and run it. Pass a Command instance or its class name:

```php
use Framework\CLI\Console;

$console = new Console();
$console->addCommand(GreetCommand::class);
$console->run();
```

3. Call it from the terminal:

```bash
php app greet Alice          # Hello, Alice!
php app greet Alice -s       # HELLO, ALICE!
php app help greet           # auto generated usage output
```

`run()` is invoked automatically. The `Console` parses argv for you: everything before the first option is available via `getArgument()`. Only long options carry a value (`--option=value`); short options like `-o` are always boolean flags, so `-o value` sets `o` to `true` and pushes `value` into the arguments. Commands can also declare `setAliases()` to be reachable by multiple names and `setGroup()` to organize them in the `index` listing.

## Installation
```bash
composer require webisters/cli
```

## Requirements
- PHP: `>=8.2`
- Composer: Compatible with Composer 2.x.

## Documentation
- Guide: https://docs.webisters.com/guides/libraries/cli/
- Package: https://webisters.com/packages/cli

## Included in Webisters Framework
If you're building a full Webisters application, install the framework meta-package:

```bash
composer require webisters/framework
```

## Development
```bash
composer install
vendor/bin/phpunit
```
Follow consistent coding style and run available linters before opening pull requests.

## Support
- Issues: https://github.com/webisters/cli/issues
- Source: https://github.com/webisters/cli
- Documentation: https://webisters.com
- Forum: https://github.com/webisters/forum
- Email: support@webisters.com

## License
MIT
