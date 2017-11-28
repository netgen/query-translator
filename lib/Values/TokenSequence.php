<?php

namespace QueryTranslator\Values;

/**
 * Token sequence holds an array of tokens extracted from the query string.
 *
 * @see \QueryTranslator\Tokenizing::tokenize()
 * @see \QueryTranslator\Values\Token
 */
class TokenSequence
{
    /**
     * An array of tokens extracted from the input string.
     *
     * @var \QueryTranslator\Values\Token[]
     */
    public $tokens;

    /**
     * Source query string, unmodified.
     *
     * @var string
     */
    public $source;

    /**
     * @param \QueryTranslator\Values\Token[] $tokens
     * @param string $source
     */
    public function __construct(array $tokens, $source)
    {
        $this->tokens = $tokens;
        $this->source = $source;
    }
}
