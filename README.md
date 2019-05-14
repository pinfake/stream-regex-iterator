# stream-regex-iterator

Find regular expresion matches on text streams and return them as an iterator.

This iterator comes as a more or less natural solution to the problem of solving complex 
(multi line) regular expressions on big files without exhausting memory.

The iterator will read chunks of data from the stream and try the regular expression 
using a preg_match_all() on each one of them.
It will also re-seek the stream and choose different chunk sizes so that it gives 
 the best possible chance to find results.
