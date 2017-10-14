<?php

namespace QueryTranslator;

use QueryTranslator\Values\TokenSequence;

/**
 * Interface for parsing a sequence of tokens into a syntax tree.
 */
interface Parsing
{
    /**
     * Parse the given $tokenSequence.
     *
     * @param \QueryTranslator\Values\TokenSequence $tokenSequence
     *
     * @return \QueryTranslator\Values\SyntaxTree
     */
    public function parse(TokenSequence $tokenSequence);
}
