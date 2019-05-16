# stream-regex-iterator
Find regular expresion matches on seekable text streams and return them inside an iterator.

## Description
This iterator comes as a solution to having to run complex multi line regular expressions 
on big files without exhausting memory.

The iterator will read chunks of data from the stream and run ```preg_match_all()``` 
on each one of them.

The iterator will read chunks of data in a way that it ensures no possible matches are lost 
through chunks, (stream being cut in the middle of a possible match).

The iterator will return matches as ```preg_match_all()``` would do when using the 
```PREG_SET_ORDER | PREG_OFFSET_CAPTURE``` flags.

## Limitations
The specified stream must be fully seekable (back and forward).

The specified buffer size must be able to store the longest possible full match of the regexp.

The iterator will require approximately twice the specified buffer size memory.


## Requirements
The following versions of PHP are supported.

* PHP 5.6
* PHP 7.0
* PHP 7.1
* PHP 7.2
* PHP 7.3

## Installation:
```
composer require antevenio/stream-regex-iterator
```

## Usage
```php
$inputString = "line1\nline2\nline3\nline4\nline5\nstart\nline6\nline7\nend";

$stream = fopen("data://text/plain," . $inputString, "r");

$matches = new Antevenio\StreamRegexIterator\Iterator(
    "/^start.*?end$/sm",
    $stream,
    32
);

foreach ($matches as $match) {
    print_r($match);
}
```
Would output:
```
Array
(
    [0] => Array
        (
            [0] => start
line6
line7
end
            [1] => 30
        )

)
```
