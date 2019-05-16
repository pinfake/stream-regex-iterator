# stream-regex-iterator

Find regular expresion matches on text streams and return them as an iterator.

## Description

This iterator comes as a solution to having to run complex multi line regular expressions 
on big files without exhausting memory.

The iterator will read chunks of data from the stream and try the regular expression 
using a preg_match_all() on each one of them.

The iterator will read chunks of data in a way that it ensures no possible matches are lost 
through chunks, (stream being cut in the middle of a possible match).

The specified buffer size must be able to store the longest possible full match of the regexp.

The iterator will compsume approximately twice the specified buffer size memory.

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
 
