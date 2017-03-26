<?php

namespace QueryTranslator\Values;

/**
 * Represents a correction applied during parsing of the token sequence.
 *
 * @see \QueryTranslator\Parsing
 * @see \QueryTranslator\Values\TokenSequence
 */
class Correction
{
    /**
     * Correction type constant.
     *
     * Defined by the language implementation.
     *
     * @var mixed
     */
    public $type;

    /**
     * An array of tokens that correction affects.
     *
     * @var \QueryTranslator\Values\Token[]
     */
    public $tokens = [];

    /**
     * @param mixed $type
     * @param \QueryTranslator\Values\Token[] ...$tokens
     */
    public function __construct($type, Token ...$tokens)
    {
        $this->type = $type;
        $this->tokens = $tokens;
    }
}
