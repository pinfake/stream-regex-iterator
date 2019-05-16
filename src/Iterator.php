<?php

namespace Antevenio\StreamRegexIterator;

use Exception;

class Iterator implements \Iterator
{
    const DEFAULT_BUFFER_SIZE = 1024 * 1024;

    protected $handle;
    protected $buffer;
    protected $bufferSize;
    protected $currentBufferSize;
    protected $regularExpression;
    protected $currentMatches;
    protected $currentMatchIndex;
    protected $globalMatchIndex;
    protected $streamFinished;
    protected $seekPoint;
    protected $pregMatchReturnValue;
    protected $readBufferSize;

    public function __construct(
        $handle,
        $regularExpression,
        $bufferSize = self::DEFAULT_BUFFER_SIZE
    ) {
        $this->handle = $handle;
        $this->regularExpression = $regularExpression;
        $this->bufferSize = $bufferSize;
    }

    /**
     * @throws Exception
     */
    public function rewind()
    {
        $this->streamFinished = false;
        $this->globalMatchIndex = 0;
        $this->currentMatchIndex = 0;
        $this->seekPoint = 0;
        $this->currentBufferSize = $this->bufferSize;
        $this->readNextChunk();
    }

    /**
     * @throws Exception
     */
    protected function readNextChunk()
    {
        do {
            $this->readIntoBuffer();

            if ($this->readBufferIsEmpty()) {
                $this->setStreamFinished();
                return;
            }

            $this->findMatches();

            if (!$this->isLastChunk()) {
                $this->prepareForNextBufferRead();
            }
        } while ($this->noMatchesFound());
    }

    protected function readIntoBuffer()
    {
        $this->buffer = fread($this->handle, $this->currentBufferSize);
        $this->readBufferSize = strlen($this->buffer);
    }

    protected function readBufferIsEmpty()
    {
        return $this->readBufferSize < 1;
    }

    protected function setStreamFinished()
    {
        $this->streamFinished = true;
    }

    protected function findMatches()
    {
        $this->pregMatchReturnValue = preg_match_all(
            $this->regularExpression,
            $this->buffer,
            $this->currentMatches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        if ($this->pregMatchReturnValue === false) {
            throw new Exception(
                "RegExp Error!: " .
                array_flip(get_defined_constants(true)['pcre'])[preg_last_error()]
            );
        }
    }

    protected function isLastChunk()
    {
        return !$this->readBufferIsFull();
    }

    protected function readBufferIsFull()
    {
        return $this->readBufferSize == $this->bufferSize;
    }

    protected function prepareForNextBufferRead()
    {
        if ($this->noMatchesFound()) {
            if ($this->isSingleBufferSize()) {
                $this->rewindToLastChunk();
                $this->setDoubleBufferSize();
            } else {
                $this->seekForward();
            }
        } else {
            $this->setSingleBufferSize();
            $this->seekAfterLastMatch();
        }
    }

    protected function noMatchesFound()
    {
        return $this->pregMatchReturnValue < 1;
    }

    protected function isSingleBufferSize()
    {
        return $this->currentBufferSize == $this->bufferSize;
    }

    protected function setDoubleBufferSize()
    {
        $this->currentBufferSize = $this->bufferSize * 2;
    }

    protected function seekForward()
    {
        $this->seekPoint += $this->bufferSize;
        fseek($this->handle, $this->seekPoint);
    }

    protected function setSingleBufferSize()
    {
        $this->currentBufferSize = $this->bufferSize;
    }

    protected function rewindToLastChunk()
    {
        fseek($this->handle, $this->seekPoint);
    }

    protected function seekAfterLastMatch()
    {
        $this->seekPoint += $this->getAfterLastMatchPosition();
        fseek($this->handle, $this->seekPoint);
    }

    protected function getAfterLastMatchPosition()
    {
        $lastMatch = $this->currentMatches[count($this->currentMatches) - 1];
        return $lastMatch[0][1] + strlen($lastMatch[0][0]);
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
        if ($this->needToReadNextChunk()) {
            $this->currentMatchIndex = 0;
            $this->readNextChunk();
        }
    }

    protected function needToReadNextChunk()
    {
        return $this->currentMatchIndex == count($this->currentMatches);
    }

    public function valid()
    {
        return !$this->streamIsFinished();
    }

    protected function streamIsFinished()
    {
        return $this->streamFinished;
    }

    public function key()
    {
        return $this->globalMatchIndex;
    }
}
