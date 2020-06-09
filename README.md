![PHPUnit](https://github.com/clayfreeman/string-stream/workflows/PHPUnit/badge.svg)
![PHPStan](https://github.com/clayfreeman/string-stream/workflows/PHPStan/badge.svg)
[![Maintainability](https://api.codeclimate.com/v1/badges/1f8b86e65ff9b355b894/maintainability)](https://codeclimate.com/github/clayfreeman/string-stream/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1f8b86e65ff9b355b894/test_coverage)](https://codeclimate.com/github/clayfreeman/string-stream/test_coverage)

# string-stream

This package provides a library to facilitate interacting with strings using the
[PSR-7 stream interface].

**NOTE**: This project is currently under active development. Usage is
discouraged until version 1.0.0 is released.

The goal of this package is to provide similar functionality in PHP to that of
C++'s `iostream`; Analagous methods are intended to behave similarly.

## Installation

To install this package using Composer, run the following command.

```bash
composer require clayfreeman/string-stream
```

Alternatively, the package may be downloaded from the [release page] on GitHub.

## Usage

Some general usage examples are shown below. For more detailed information,
please refer to the [PSR-7 stream interface].

```php
use ClayFreeman\Stream\StringStream;

$stream = new StringStream('sample string');
var_export((string) $stream); // 'sample string'

// Reset the stream offset to the beginning.
var_export($stream->read(2)); // 'sa'
var_export($stream->getContents()); // 'mple string'

$stream->write('some more contents');
var_export((string) $stream); // 'sample stringsome more contents'
```

## Testing

Tests for this package are written using PHPUnit 9. To run the tests, you'll
need to have PHP 7.4 and Composer installed:

```bash
git clone https://github.com/clayfreeman/string-stream.git
cd string-stream && ./vendor/bin/phpunit
```

# License

This project is subject to the terms of the Mozilla Public License, v2.0. Please
refer to `LICENSE.txt` for more information, or visit the following URL to get a
copy of the license: http://mozilla.org/MPL/2.0/

[PSR-7 stream interface]: https://www.php-fig.org/psr/psr-7/#34-psrhttpmessagestreaminterface
[release page]: https://github.com/clayfreeman/string-stream/releases
