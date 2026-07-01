# Nil Kernel

A lightweight PHP kernel built on Symfony components.

## Requirements

- PHP >= 8.2.0

## Installation

```bash
composer require nil/kernel
```

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Nil\Kernel\Kernel;

Kernel::boot();
```

## Features

- **Error Handling**: Based on `symfony/error-handler`
- **Event Dispatcher**: Based on `symfony/event-dispatcher`
- **HTTP Foundation**: Based on `symfony/http-foundation`
- **Routing**: Based on `symfony/routing`
- **Console**: Based on `symfony/console`
- **Cache**: Based on `symfony/cache`
- **Database**: Based on `doctrine/dbal`
- **Logging**: Based on `monolog/monolog`

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
