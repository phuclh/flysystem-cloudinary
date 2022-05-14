# A Laravel flysystem driver for Cloudinary

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phuclh/flysystem-cloudinary.svg?style=flat-square)](https://packagist.org/packages/phuclh/flysystem-cloudinary)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/phuclh/flysystem-cloudinary/run-tests?label=tests)](https://github.com/phuclh/flysystem-cloudinary/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/phuclh/flysystem-cloudinary/Check%20&%20fix%20styling?label=code%20style)](https://github.com/phuclh/flysystem-cloudinary/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/phuclh/flysystem-cloudinary.svg?style=flat-square)](https://packagist.org/packages/phuclh/flysystem-cloudinary)

A Laravel flysystem driver for Cloudinary

## Installation

You can install the package via composer:

```bash
composer require phuclh/flysystem-cloudinary
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="flysystem-cloudinary-config"
```

## Setup

First you will need to sing up for a [Cloudinary](https://cloudinary.com/) account. Then go [https://cloudinary.com/console](https://cloudinary.com/console) to get your url endpoint. Add the following to your .env file:

```bash
CLOUDINARY_URL=cloudinary://.....@id
```

## Usage

```php
// Upload file (second argument can be an url, file or base64)
Storage::disk('cloudinary')->put('filename.jpg', 'https://mysite.com/my_image.com');

// Get file
Storage::disk('cloudinary')->get('filename.jpg');

// Delete file
Storage::disk('cloudinary')->delete('filename.jpg');

// List all files 
Storage::disk('cloudinary')->listContents('', false); // listContents($path, $deep)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [phucle](https://github.com/phuclh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
