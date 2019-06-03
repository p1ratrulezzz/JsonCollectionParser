# JsonCollectionParser
[![Build Status](https://travis-ci.org/p1ratrulezzz/JsonCollectionParser.svg?branch=master)](https://travis-ci.org/p1ratrulezzz/JsonCollectionParser)

[![Latest Stable Version](https://poser.pugx.org/p1ratrulezzz/json-collection-parser/v/stable)](https://packagist.org/packages/p1ratrulezzz/json-collection-parser)
[![Total Downloads](https://poser.pugx.org/p1ratrulezzz/json-collection-parser/downloads)](https://packagist.org/packages/p1ratrulezzz/json-collection-parser)
[![composer.lock](https://poser.pugx.org/p1ratrulezzz/json-collection-parser/composerlock)](https://packagist.org/packages/p1ratrulezzz/json-collection-parser)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)

Event-based parser for large JSON collections (consumes small amount of memory).
Built on top of [JSON Streaming Parser](https://github.com/salsify/jsonstreamingparser)

This package is compliant with [PSR-4](http://www.php-fig.org/psr/4/), [PSR-1](http://www.php-fig.org/psr/1/), and [PSR-2](http://www.php-fig.org/psr/2/).
If you notice compliance oversights, please send a patch via pull request.

## Installation
You will need [Composer](https://getcomposer.org/) to install the package
```bash
composer require p1ratrulezzz/json-collection-parser ~1.5
```

## Input data format
Collection must be an array of objects.
```javascript
[
    {
        "id": 78,
        "title": "Title",
        "dealType": "sale",
        "propertyType": "townhouse",
        "properties": {
            "bedroomsCount": 6,
            "parking": "yes"
        },
        "photos": [
            "1.jpg",
            "2.jpg"
        ]
    },
    {
        "id": 729,
        "dealType": "rent_long",
        "propertyType": "villa"
    },
    {
        "id": 5165,
        "dealType": "rent_short",
        "propertyType": "villa"
    }
]
```

## Usage
Function as callback:
```php
function processItem(array $item)
{
    is_array($item); //true
    print_r($item);
}

$parser = new \JsonCollectionParser\Parser();
$parser->parse('/path/to/file.json', 'processItem');
```

Closure as callback:
```php
$items = [];

$parser = new \JsonCollectionParser\Parser();
$parser->parse('/path/to/file.json', function (array $item) use (&$items) {
    $items[] = $item;
});
```

Static method as callback:
```php
class ItemProcessor {
    public static function process(array $item)
    {
        is_array($item); //true
        print_r($item);
    }
}

$parser = new \JsonCollectionParser\Parser();
$parser->parse('/path/to/file.json', ['ItemProcessor', 'process']);
```

Instance method as callback:
```php
class ItemProcessor {
    public function process(array $item)
    {
        is_array($item); //true
        print_r($item);
    }
}

$parser = new \JsonCollectionParser\Parser();
$processor = new \ItemProcessor();
$parser->parse('/path/to/file.json', [$processor, 'process']);
```

Receive items as objects:
```php
function processItem(\stdClass $item)
{
    is_array($item); //false
    is_object($item); //true
    print_r($item);
}

$parser = new \JsonCollectionParser\Parser();
$parser->parseAsObjects('/path/to/file.json', 'processItem');
```

Pass stream as parser input:
```php
$stream = fopen('/path/to/file.json', 'r');

$parser = new \JsonCollectionParser\Parser();
$parser->parseAsObjects($stream, 'processItem');
```

## Supported file formats

* `.json` - raw JSON format
* `.gz` - GZIP-compressed file (you will need `zlib` PHP extension installed)

## Running tests
```bash
composer test
```

## License
This library is released under [MIT](http://www.tldrlegal.com/license/mit-license) license.
