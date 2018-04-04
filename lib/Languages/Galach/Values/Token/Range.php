<?php

namespace QueryTranslator\Languages\Galach\Values\Token;

use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Values\Token;

/**
 * Range term token.
 *
 * @see \QueryTranslator\Languages\Galach\Tokenizer::TOKEN_TERM
 */
final class Range extends Token
{
    /**
     * Holds domain string.
     *
     * @var string
     */
    public $domain;

    /**
     * @var string
     */
    public $rangeFrom;

    /**
     * @var string
     */
    public $rangeTo;

    /**
     * @param string $lexeme
     * @param int $position
     * @param string $domain
     * @param string $rangeFrom
     * @param string $rangeTo
     */
    public function __construct($lexeme, $position, $domain, $rangeFrom, $rangeTo)
    {
        parent::__construct(Tokenizer::TOKEN_TERM, $lexeme, $position);

        $this->domain = $domain;
        $this->rangeFrom = $rangeFrom;
        $this->rangeTo = $rangeTo;
    }
}
