<?php

namespace Antevenio\StreamRegexIterator;

use Exception;

class Iterator implements \Iterator
{
    const DEFAULT_BUFFER_SIZE = 1024 * 1024;

    protected $handler;
    protected $buffer;
    protected $bufferSize;
    protected $currentBufferSize;
    protected $regularExpression;
    protected $currentMatches;
    protected $currentMatchIndex;
    protected $globalMatchIndex;
    protected $streamEnded;
    protected $seekPoint;

    public function __construct(
        $handler,
        $regularExpression,
        $bufferSize = self::DEFAULT_BUFFER_SIZE
    ) {
        $this->handler = $handler;
        $this->regularExpression = $regularExpression;
        $this->bufferSize = $bufferSize;
    }

    /**
     * @throws Exception
     */
    public function rewind()
    {
        $this->streamEnded = false;
        $this->globalMatchIndex = 0;
        $this->seekPoint = 0;
        $this->currentBufferSize = $this->bufferSize;
        $this->readNextBlock();
    }

    /**
     * @throws Exception
     */
    protected function readNextBlock()
    {
        do {
            fseek($this->handler, $this->seekPoint);
            if (feof($this->handler)) {
                $this->streamEnded = true;

                return;
            }
            $this->buffer = fread($this->handler, $this->currentBufferSize);
            $ret = preg_match_all(
                $this->regularExpression,
                $this->buffer,
                $this->currentMatches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            );
            if ($ret === false) {
                throw new Exception(
                    "RegExp Error!: " .
                    array_flip(get_defined_constants(true)['pcre'])[preg_last_error()]
                );
            }
            if ($ret < 1) {
                $this->handleNoResultsFoundInChunk();
            } else {
                $this->resetCurrentBufferSize();
            }
        } while ($ret < 1);

        $this->updateSeekPosition();
        $this->currentMatchIndex = 0;
    }

    protected function resetCurrentBufferSize()
    {
        $this->currentBufferSize = $this->bufferSize;
    }

    protected function doubleCurrentBufferSize()
    {
        $this->currentBufferSize += $this->bufferSize;
    }

    protected function handleNoResultsFoundInChunk()
    {
        if ($this->currentBufferSize == $this->bufferSize) {
            $this->doubleCurrentBufferSize();
        } else {
            $this->seekPoint += $this->bufferSize;
        }
    }

    protected function updateSeekPosition()
    {
        $lastMatch = $this->currentMatches[count($this->currentMatches) - 1];
        $this->seekPoint += $lastMatch[0][1] + strlen($lastMatch[0][0]);
        fseek($this->handler, $this->seekPoint);
    }

    public function current()
    {
        return $this->currentMatches[$this->currentMatchIndex];
    }

    /**
     * @throws Exception
     */
    public function next()
    {
        $this->currentMatchIndex++;
        $this->globalMatchIndex++;
        if ($this->currentMatchIndex == count($this->currentMatches)) {
            $this->readNextBlock();
        }
    }

    public function valid()
    {
        return !$this->streamEnded;
    }

    public function key()
    {
        return $this->globalMatchIndex;
    }
}
