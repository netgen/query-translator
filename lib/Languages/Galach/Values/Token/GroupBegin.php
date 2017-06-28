<?php

namespace QueryTranslator\Languages\Galach\Values\Token;

use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Values\Token;

/**
 * GroupBegin token represents group's domain and left side delimiter.
 */
final class GroupBegin extends Token
{
    /**
     * Holds group's left side delimiter string.
     *
     * @var string
     */
    public $delimiter;

    /**
     * Holds domain string.
     *
     * @var string
     */
    public $domain;

    /**
     * @param string $lexeme
     * @param int $position
     * @param string $domain
     * @param string $delimiter
     */
    public function __construct($lexeme, $position, $delimiter, $domain)
    {
        $this->delimiter = $delimiter;
        $this->domain = $domain;

        parent::__construct(Tokenizer::TOKEN_GROUP_BEGIN, $lexeme, $position);
    }
}
