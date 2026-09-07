<?php declare(strict_types=1);
/*
 * This file is part of Webisters CLI Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\CLI\Commands;

use Framework\CLI\CLI;
use Framework\CLI\Command;
use Framework\CLI\Styles\ForegroundColor;

/**
 * Class Help.
 *
 * @package cli
 */
class Help extends Command
{
    protected string $name = 'help';
    protected string $usage = 'help [command_name]';

    public function run() : void
    {
        $commandName = $this->console->getArgument(0);
        if ($commandName === null || $commandName === '') {
            $commandName = $this->console->getCommandName();
        } elseif (!$this->console->hasCommand($commandName)
            && $this->console->getCommandName() !== 'help') {
            // The help option was passed to a command that takes arguments,
            // so the first argument is a value and not a command name.
            $commandName = $this->console->getCommandName();
        }
        if ($commandName === '') {
            $commandName = 'help';
        }
        $this->showCommand($commandName);
    }

    protected function showCommand(string $commandName) : void
    {
        $command = $this->console->getCommand($commandName);
        if ($command === null) {
            CLI::error(
                $this->console->getLanguage()->render('cli', 'commandNotFound', [$commandName]),
                \defined('TESTING') ? null : 1
            );
            return;
        }
        CLI::write(CLI::style(
            $this->console->getLanguage()->render('cli', 'command') . ': ',
            ForegroundColor::green
        ) . $command->getName());
        $value = $command->getAliases();
        if ($value) {
            CLI::write(CLI::style(
                $this->console->getLanguage()->render('cli', 'aliases') . ': ',
                ForegroundColor::green
            ) . \implode(', ', $value));
        }
        $value = $command->getGroup();
        if ($value !== null) {
            CLI::write(CLI::style(
                $this->console->getLanguage()->render('cli', 'group') . ': ',
                ForegroundColor::green
            ) . $value);
        }
        $value = $command->getDescription();
        if ($value !== '') {
            CLI::write(CLI::style(
                $this->console->getLanguage()->render('cli', 'description') . ': ',
                ForegroundColor::green
            ) . $value);
        }
        $value = $command->getUsage();
        if ($value !== '') {
            CLI::write(CLI::style(
                $this->console->getLanguage()->render('cli', 'usage') . ': ',
                ForegroundColor::green
            ) . $value);
        }
        $this->showArguments($command);
        $this->showOptions($command);
    }

    /**
     * Print the Arguments block from the command definitions.
     *
     * @param Command $command The command being documented
     */
    protected function showArguments(Command $command) : void
    {
        $definitions = $command->getArgumentDefinitions();
        if (!$definitions) {
            return;
        }
        CLI::write(
            $this->console->getLanguage()->render('cli', 'arguments') . ':',
            ForegroundColor::green
        );
        $lastKey = \array_key_last($definitions);
        foreach ($definitions as $position => $definition) {
            $label = $this->definitionLabel($position, $definition);
            CLI::write('  ' . $label . '  ' . $this->describeDefinition($definition));
            $raw = $definition['description'] ?? null;
            $description = \is_string($raw) ? \trim($raw) : '';
            if ($description !== '') {
                CLI::write('  ' . $this->finishSentence($description));
            }
            if ($position !== $lastKey) {
                CLI::newLine();
            }
        }
        CLI::newLine();
    }

    /**
     * Print the Options block, derived from the option definitions when the
     * command declares them and completed with the legacy free text options
     * map for entries that are not covered by a definition.
     *
     * @param Command $command The command being documented
     */
    protected function showOptions(Command $command) : void
    {
        $definitions = $command->getOptionDefinitions();
        $legacy = $command->getOptions();
        if (!$definitions && !$legacy) {
            return;
        }
        CLI::write(
            $this->console->getLanguage()->render('cli', 'options') . ':',
            ForegroundColor::green
        );
        $entries = [];
        foreach ($definitions as $key => $definition) {
            $raw = $definition['description'] ?? null;
            $description = \is_string($raw) ? \trim($raw) : '';
            if ($description === '') {
                $description = \trim((string) ($this->findLegacyDescription((string) $key, $legacy) ?? ''));
            }
            $entries[$this->formatOptionKey((string) $key)] = [
                'meta' => $this->describeDefinition($definition),
                'description' => $description,
            ];
        }
        foreach ($legacy as $key => $description) {
            $display = $this->formatOptionKey((string) $key);
            if (!\array_key_exists($display, $entries)) {
                $entries[$display] = [
                    'meta' => '',
                    'description' => \trim((string) $description),
                ];
            }
        }
        \ksort($entries);
        $lastKey = \array_key_last($entries);
        foreach ($entries as $option => $entry) {
            CLI::write('  ' . $this->setColor($option));
            if ($entry['meta'] !== '') {
                CLI::write('  ' . $entry['meta']);
            }
            if ($entry['description'] !== '') {
                CLI::write('  ' . $this->finishSentence($entry['description']));
            }
            if ($option !== $lastKey) {
                CLI::newLine();
            }
        }
    }

    /**
     * Build the meta description of a definition, like
     * "required, int, default \"5\"".
     *
     * @param array<string,mixed> $definition The argument or option definition
     */
    protected function describeDefinition(array $definition) : string
    {
        $parts = [!empty($definition['required']) ? 'required' : 'optional'];
        $type = $definition['type'] ?? 'string';
        if (\is_string($type) && $type !== '') {
            $parts[] = $type;
        }
        $default = $definition['default'] ?? null;
        if (\is_scalar($default)) {
            $parts[] = 'default ' . (\is_bool($default)
                ? ($default ? 'true' : 'false')
                : '"' . $default . '"');
        }
        return \implode(', ', $parts);
    }

    /**
     * Normalize an option key for display, adding dashes to definition keys
     * and keeping legacy keys as they were written.
     */
    protected function formatOptionKey(string $key) : string
    {
        if ($key !== '' && $key[0] === '-') {
            return $key;
        }
        return \strlen($key) > 1 ? '--' . $key : '-' . $key;
    }

    /**
     * Find the legacy free text description of a definition key.
     *
     * @param array<string,bool|string> $legacy The legacy options map
     */
    protected function findLegacyDescription(string $key, array $legacy) : ?string
    {
        foreach ([$key, '-' . $key, '--' . $key] as $candidate) {
            if (\array_key_exists($candidate, $legacy)) {
                $value = $legacy[$candidate];
                return \is_bool($value) ? '' : $value;
            }
        }
        return null;
    }

    /**
     * Make sure a description ends with a sentence dot.
     */
    protected function finishSentence(string $description) : string
    {
        if (!\str_ends_with($description, '.')) {
            $description .= '.';
        }
        return $description;
    }

    protected function setColor(string $text) : string
    {
        $text = \explode(',', $text);
        foreach ($text as &$item) {
            $item = CLI::style($item, ForegroundColor::yellow);
        }
        return \implode(', ', $text);
    }

    public function getDescription() : string
    {
        return $this->console->getLanguage()->render('cli', 'help.description');
    }
}
