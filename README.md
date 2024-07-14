# Homeful Mortgage Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jn-devops/mortgage.svg?style=flat-square)](https://packagist.org/packages/jn-devops/mortgage)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jn-devops/mortgage/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jn-devops/mortgage/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jn-devops/mortgage/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jn-devops/mortgage/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jn-devops/mortgage.svg?style=flat-square)](https://packagist.org/packages/jn-devops/mortgage)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require jn-devops/mortgage
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="mortgage-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="mortgage-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="mortgage-views"
```

## Usage

```php
$mortgage = new Homeful\Mortgage();
echo $mortgage->echoPhrase('Hello, Homeful!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Lester B. Hurtado](https://github.com/jn-devops)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
