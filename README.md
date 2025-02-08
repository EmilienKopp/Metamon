# Metamon

![Tests](https://img.shields.io/github/actions/workflow/status/emilienkopp/metamon/tests.yml?label=tests)
![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)
![Laravel Version](https://img.shields.io/badge/laravel-8.x%20%7C%209.x%20%7C%2010.x-red)

A Laravel package for handling model metadata with role-based access control.

## Installation

You can install the package via composer:

```bash
composer require splitstack/laravel-metamon
```

## Usage

1. Add the HasMetadata trait to your model:

```php
use Splitstack\Metamon\HandlesMetadata;

class YourModel extends Model
{
    use HandlesMetadata;
}
```

2. Publish the config file:

```bash
php artisan vendor:publish --tag="metamon-config"
```

3. Make sure your model has a `metadata` column (JSON type):

```php
$table->json('metadata')->nullable();
```

## Usage Examples

```php
// Get metadata
$model->metadata('key');
$model->getMetadata('key', 'default');

// Set metadata
$model->metadata('key', 'value');
$model->setMetadata('key', 'value', 'admin');

// Check if metadata exists
$model->hasMetadata('key');

// Remove metadata
$model->forgetMetadata('key');

// Query by metadata
YourModel::whereMetadata('key', 'value')->get();
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
