<?php

namespace Antevenio\StreamRegexIterator\Test;

use Antevenio\StreamRegexIterator\Iterator;

class IteratorTest extends \PHPUnit_Framework_TestCase
{
    public function iteratorDataProvider()
    {
        return [
            [
                "/^start.*?end$/sm",
                "line1\nline2\nline3\nline4\nline5\nstart\nline6\nline7\nend",
                32,
                [
                    [
                        [ "start\nline6\nline7\nend", 30 ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider iteratorDataProvider
     * @param $regularExpression
     * @param $inputString
     * @param $bufferSize
     * @param $expectedMatches
     */
    public function testIterator(
        $regularExpression,
        $inputString,
        $bufferSize,
        $expectedMatches
    ) {
        $stream = fopen('data://text/plain,' . $inputString, 'r');
        $iterator = new Iterator(
            $stream,
            $regularExpression,
            $bufferSize
        );
        $matches = iterator_to_array($iterator);
        fclose($stream);
        $this->assertEquals($expectedMatches, $matches);
    }
}
