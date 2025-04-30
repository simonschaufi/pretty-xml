# pretty-xml

A tiny library for pretty printing XML, inspired purely from DomDocument's lack
of ability to configure indent distance.

[![Latest Stable Version](https://poser.pugx.org/simonschaufi/pretty-xml/v/stable)](https://packagist.org/packages/simonschaufi/pretty-xml)
[![Total Downloads](https://poser.pugx.org/simonschaufi/pretty-xml/downloads)](https://packagist.org/packages/simonschaufi/pretty-xml)

## Usage

### Installation

The recommended way to install the extension is using [Composer][1].

Run the following command:

```bash
composer require simonschaufi/pretty-xml
```

### How to use

To use, give it a badly indented (but well-formed and valid) XML string:

```php
use PrettyXml\Formatter;

$formatter = new Formatter();
echo "<pre>" . htmlspecialchars($formatter->format('<?xml version="1.0" encoding="UTF-8"?><foo><bar>Baz</bar></foo>')) . "</pre>";
```

You can also change the size of the indent:

```php
$formatter->setIndentSize(2);
```

And you can change the indent character:

```php
$formatter->setIndentCharacter("\t");
```

[1]: https://getcomposer.org/
