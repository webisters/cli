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
- `run()` parses argv, matches the requested command (including aliases) and dispatches to `exec()`
- Unknown commands fall back to `Index` (which lists available commands) and suggest the closest matching command name
- `getArgument()`, `getArguments()`, `getOption()` and `getOptions()` expose the parsed command line

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

`run()` is invoked automatically. The `Console` parses argv for you: everything before the first option is available via `getArgument()`, and `--option=value` or `-o value` style options via `getOption()`. Commands can also declare `setAliases()` to be reachable by multiple names and `setGroup()` to organize them in the `index` listing.

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
