Opis Http
=========
[![Build Status](https://travis-ci.org/opis/http.svg?branch=master)](https://travis-ci.org/opis/http)
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

**Opis Http** is licensed under the [Apache License, Version 2.0][apache_license].

### Requirements

* PHP ^7.1
* [Opis Stream][stream]

## Installation

**Opis Http** is available on [Packagist] and it can be installed from a 
command line interface by using [Composer]. 

```bash
composer require opis/http
```

Or you could directly reference it into your `composer.json` file as a dependency

```json
{
    "require": {
        "opis/http": "^3.0"
    }
}
```

[stream]: https://github.com/opis/stream
[documentation]: https://www.opis.io/http
[apache_license]: https://www.apache.org/licenses/LICENSE-2.0 "Apache License"
[Packagist]: https://packagist.org/packages/opis/http "Packagist"
[Composer]: https://getcomposer.org "Composer"
