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
    public $startType;

    /**
     * @var string
     */
    public $endType;

    /**
     * @param string $lexeme
     * @param int    $position
     * @param string $domain
     * @param string $rangeFrom
     * @param string $rangeTo
     * @param string $startType
     * @param string $endType
     */
    public function __construct($lexeme, $position, $domain, $rangeFrom, $rangeTo, $startType, $endType)
    {
        $this->ensureValidType($startType);
        $this->ensureValidType($endType);

        parent::__construct(Tokenizer::TOKEN_TERM, $lexeme, $position);

        $this->domain = $domain;
        $this->rangeFrom = $rangeFrom;
        $this->rangeTo = $rangeTo;
        $this->startType = $startType;
        $this->endType = $endType;
    }

    private function ensureValidType($type)
    {
        if (!in_array($type, [self::TYPE_EXCLUSIVE, self::TYPE_INCLUSIVE])) {
            throw new \InvalidArgumentException(sprintf('Invalid range type: %s', $type));
        }
    }
}
