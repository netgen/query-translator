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
    const TYPE_INCLUSIVE = 'inclusive';
    const TYPE_EXCLUSIVE = 'exclusive';

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
     * @var string
     */
    public $type;

    /**
     * @param string $lexeme
     * @param int    $position
     * @param string $domain
     * @param string $rangeFrom
     * @param string $rangeTo
     * @param string $type
     */
    public function __construct($lexeme, $position, $domain, $rangeFrom, $rangeTo, $type)
    {
        if (!in_array($type, [self::TYPE_EXCLUSIVE, self::TYPE_INCLUSIVE])) {
            throw new \InvalidArgumentException(sprintf('Invalid range type: %s', $type));
        }

        parent::__construct(Tokenizer::TOKEN_TERM, $lexeme, $position);

        $this->domain = $domain;
        $this->rangeFrom = $rangeFrom;
        $this->rangeTo = $rangeTo;
        $this->type = $type;
    }

    /**
     * Returns the range type, given the starting symbol.
     *
     * @param string $startSymbol the start symbol, either '[' or '{'
     *
     * @return string
     */
    public static function getTypeByStart($startSymbol)
    {
        if ('[' === $startSymbol) {
            return self::TYPE_INCLUSIVE;
        }

        if ('{' === $startSymbol) {
            return self::TYPE_EXCLUSIVE;
        }

        throw new \InvalidArgumentException(sprintf('Invalid range start symbol: %s', $startSymbol));
    }
}
