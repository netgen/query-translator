<?php

namespace QueryTranslator\Languages\Galach;

use QueryTranslator\Tokenizing;
use QueryTranslator\Values\TokenSequence;

/**
 * Galach implementation of the Tokenizing interface.
 */
final class Tokenizer implements Tokenizing
{
    /**
     * Represents the whitespace in the input string.
     */
    const TOKEN_WHITESPACE = 1;

    /**
     * Combines two adjoining elements with logical AND.
     */
    const TOKEN_LOGICAL_AND = 2;

    /**
     * Combines two adjoining elements with logical OR.
     */
    const TOKEN_LOGICAL_OR = 4;

    /**
     * Applies logical NOT to the next (right-side) element.
     */
    const TOKEN_LOGICAL_NOT = 8;

    /**
     * Applies logical NOT to the next (right-side) element.
     *
     * This is an alternative to the TOKEN_LOGICAL_NOT, with the difference that
     * parser will expect it's placed next (left) to the element it applies to,
     * without the whitespace in between.
     */
    const TOKEN_LOGICAL_NOT_2 = 16;

    /**
     * Mandatory operator applies to the next (right-side) element and means
     * that the element must be present. There must be no whitespace between it
     * and the element it applies to.
     */
    const TOKEN_MANDATORY = 32;

    /**
     * Prohibited operator applies to the next (right-side) element and means
     * that the element must not be present. There must be no whitespace between
     * it and the element it applies to.
     */
    const TOKEN_PROHIBITED = 64;

    /**
     * Left side delimiter of a group.
     *
     * Group is used to group elements in order to form a sub-query.
     *
     * @see \QueryTranslator\Languages\Galach\Values\Token\GroupBegin
     */
    const TOKEN_GROUP_BEGIN = 128;

    /**
     * Right side delimiter of a group.
     *
     * Group is used to group elements in order to form a sub-query.
     */
    const TOKEN_GROUP_END = 256;

    /**
     * Term token type represents a category of term type tokens.
     *
     * This type is intended to be used as an extension point through subtyping.
     *
     * @see \QueryTranslator\Languages\Galach\Values\Token\Phrase
     * @see \QueryTranslator\Languages\Galach\Values\Token\Tag
     * @see \QueryTranslator\Languages\Galach\Values\Token\User
     * @see \QueryTranslator\Languages\Galach\Values\Token\Word
     */
    const TOKEN_TERM = 512;

    /**
     * Bailout token.
     *
     * If token could not be recognized, next character is extracted into a
     * token of this type. Ignored by parser.
     */
    const TOKEN_BAILOUT = 1024;

    /**
     * @var \QueryTranslator\Languages\Galach\TokenExtractor
     */
    private $tokenExtractor;

    /**
     * @param \QueryTranslator\Languages\Galach\TokenExtractor $tokenExtractor
     */
    public function __construct(TokenExtractor $tokenExtractor)
    {
        $this->tokenExtractor = $tokenExtractor;
    }

    public function tokenize($string)
    {
        $length = mb_strlen($string);
        $position = 0;
        $tokens = [];

        while ($position < $length) {
            $token = $this->tokenExtractor->extract($string, $position);
            $position += mb_strlen($token->lexeme);
            $tokens[] = $token;
        }

        return new TokenSequence($tokens, $string);
    }
}
