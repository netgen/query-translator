<?php

namespace QueryTranslator;

/**
 * Interface for tokenizing a string into a sequence of tokens.
 */
interface Tokenizing
{
    /**
     * Tokenize the given $string.
     *
     * @param string $string Input string
     *
     * @return \QueryTranslator\Values\TokenSequence
     */
    public function tokenize($string);
}
