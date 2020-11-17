Opis Http
=========
[![Tests](https://github.com/opis/http/workflows/Tests/badge.svg)](https://github.com/opis/http/actions)
[![Latest Stable Version](https://poser.pugx.org/opis/http/version.png)](https://packagist.org/packages/opis/http)
[![Latest Unstable Version](https://poser.pugx.org/opis/http/v/unstable.png)](https://packagist.org/packages/opis/http)
[![License](https://poser.pugx.org/opis/http/license.png)](https://packagist.org/packages/opis/http)

HTTP abstraction layer
---------------------
**Opis HTTP** is a library that provides an object-oriented representation for HTTP requests and responses.
The library was designed to be small, efficient, and easy to work with.

### Documentation

The full documentation for this library can be found [here][documentation].

### License

**Opis HTTP** is licensed under the [Apache License, Version 2.0][license].

### Requirements

* PHP ^7.4|^8.0
* [Opis Stream] ^2020

## Installation

**Opis HTTP** is available on [Packagist] and it can be installed from a 
command line interface by using [Composer]. 

```bash
composer require opis/http
```

Or you could directly reference it into your `composer.json` file as a dependency

```json
{
    "require": {
        "opis/http": "^2020"
    }
}
```

[documentation]: https://www.opis.io/http
[license]: https://www.apache.org/licenses/LICENSE-2.0 "Apache License"
[Packagist]: https://packagist.org/packages/opis/http "Packagist"
[Composer]: https://getcomposer.org "Composer"
[Opis Stream]: https://github.com/opis/stream "Opis Stream"
