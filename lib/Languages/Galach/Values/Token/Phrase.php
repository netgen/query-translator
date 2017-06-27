<?php

namespace QueryTranslator\Languages\Galach\Values\Token;

use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Values\Token;

/**
 * Phrase term token.
 *
 * @see \QueryTranslator\Languages\Galach\Tokenizer::TOKEN_TERM
 */
final class Phrase extends Token
{
    /**
     * Holds domain identifier or null if not set.
     *
     * @var null|string
     */
    public $domain;

    /**
     * @var string
     */
    public $quote;

    /**
     * @var string
     */
    public $phrase;

    /**
     * @param string $lexeme
     * @param int $position
     * @param string $domain
     * @param string $quote
     * @param string $phrase
     */
    public function __construct($lexeme, $position, $domain, $quote, $phrase)
    {
        $this->domain = $domain;
        $this->quote = $quote;
        $this->phrase = $phrase;

        parent::__construct(Tokenizer::TOKEN_TERM, $lexeme, $position);
    }
}
