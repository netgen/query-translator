<?php

namespace QueryTranslator\Languages\Galach\Values\Token;

use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Values\Token;

/**
 * Tag term token.
 *
 * @see \QueryTranslator\Languages\Galach\Tokenizer::TOKEN_TERM
 */
final class Tag extends Token
{
    /**
     * @var string
     */
    public $marker;

    /**
     * @var string
     */
    public $tag;

    /**
     * @param string $lexeme
     * @param int $position
     * @param string $marker
     * @param string $tag
     */
    public function __construct($lexeme, $position, $marker, $tag)
    {
        $this->marker = $marker;
        $this->tag = $tag;

        parent::__construct(Tokenizer::TOKEN_TERM, $lexeme, $position);
    }
}
