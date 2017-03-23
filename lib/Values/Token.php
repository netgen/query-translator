<?php

namespace QueryTranslator\Values;

/**
 * Token represents a sequence of characters which forms a syntactic unit.
 */
class Token
{
    /**
     * Token type constant.
     *
     * Categorizes the token for the purpose of parsing.
     * Defined by the language implementation.
     *
     * @var string
     */
    public $type;

    /**
     * Token lexeme is a part of the input string recognized as token.
     *
     * @var string
     */
    public $lexeme;

    /**
     * Position of the lexeme in the input string.
     *
     * @var int
     */
    public $position;

    /**
     * @param string $type
     * @param string $lexeme
     * @param int $position
     */
    public function __construct($type, $lexeme, $position)
    {
        $this->type = $type;
        $this->lexeme = $lexeme;
        $this->position = $position;
    }
}
