# toXML

This library provides the simplest and most straightforward way to switch between XML and Array.

## Requirements

- PHP 5.6 or higger
- PHP DOM Extension

## Installation

```
composer require muhammetsafak/toxml
```

## Configuration

The following array is the associative configuration array declaring identifiable configurations. It can be defined while creating an object, or it can be defined later using setter methods.

```php
array(
    'encode'            => 'UTF-8', // String
    'version'           => '1.0', // String ("1.0" or "1.1")
    'attributesKey'     => '@attibutes', // String
    'cdataKey'          => '@cdata', // String
    'valueKey'          => '@value', // String
    'useNamespaces'     => false, // Boolean
    'rootNodeName'      => 'root', // String
);
```

## Usage

### Array to XML

```php
require_once "vendor/autoload.php";

$data = array();

$xml = new \MuhammetSafak\ToXML\XML();

header('Content-Type: application/xml; charset=utf-8');
echo $xml->withArray($data)
        ->toXML();
```

### XML to Array

```php
require_once "vendor/autoload.php";

$source = file_get_contents('example.xml');

$xml = new \MuhammetSafak\ToXML\XML();
$data = $xml->withXML($source)
            ->toArray();
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT Licence](./LICENSE)
