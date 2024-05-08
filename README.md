# Small, sharp tools for Laravel developers and teams

[![Latest Version on Packagist](https://img.shields.io/packagist/v/artisan-build/scalpels.svg?style=flat-square)](https://packagist.org/packages/artisan-build/scalpels)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/artisan-build/scalpels/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/artisan-build/scalpels/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/artisan-build/scalpels/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/artisan-build/scalpels/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/artisan-build/scalpels.svg?style=flat-square)](https://packagist.org/packages/artisan-build/scalpels)

### Currently In Dog Food Mode

This package is currently undocumented an only used internally. As soon as it is ready for general use, we'll publish documentation for it and announce the release.

![In Dog Food Mode](https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8ZG9nJTIwZm9vZHxlbnwwfHwwfHx8MA%3D%3D)

You are, of course, welcome to grab it and try it out. Just be careful. It might break some stuff.


## Installation

You can install the package via composer:

```bash
composer require artisan-build/scalpels
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="scalpels-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="scalpels-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="scalpels-views"
```

## Usage

```php
$scalpels = new ArtisanBuild\Scalpels();
echo $scalpels->echoPhrase('Hello, ArtisanBuild!');
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

- [Ed Grosvenor](https://github.com/edgrosvenor)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
