# Pollora Helper Overrider

A Composer plugin that ensures Pollora framework helper functions take precedence over other helper implementations.

## Description

This Composer plugin is specifically designed for the Pollora framework ecosystem. It manipulates the Composer autoloader to ensure that Pollora's helper functions are loaded first, allowing them to override any similar helper functions that might be defined by other packages.

## Why?

In PHP applications, when multiple packages define the same helper functions, the last loaded file takes precedence. This plugin ensures that Pollora's helper functions are always loaded first, maintaining consistent behavior across your application regardless of other installed packages.

## Installation

Install the package via composer:

```bash
composer require pollora/helper-overrider
```

## How It Works

The plugin:
1. Hooks into Composer's autoload process
2. Identifies Pollora helper files
3. Modifies the autoload order to prioritize these files
4. Ensures Pollora helper functions are loaded before any other package's helpers

## Requirements

- PHP 8.2 or higher
- Composer 2.x

## Configuration

No configuration needed. The plugin works automatically as part of the Pollora framework setup.

## Note

This package is part of the Pollora framework ecosystem and is designed to work seamlessly with it. It's not intended for standalone use.

## Support

If you discover any issues, please create an issue on the Pollora framework's GitHub repository.

## License

MIT License

## Credits

Created and maintained by Pollora
