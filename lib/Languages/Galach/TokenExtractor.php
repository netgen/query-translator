<?php

namespace QueryTranslator\Languages\Galach;

use QueryTranslator\Values\Token;
use RuntimeException;

/**
 * Token extractor is used by Tokenizer to extract tokens from the input string.
 *
 * This is the abstract implementation intended to be used as an extension point.
 */
abstract class TokenExtractor
{
    /**
     * Return the token at the given $position of the $string.
     *
     * @throws \RuntimeException On PCRE regex error
     *
     * @param string $string Input string
     * @param int $position Position in the input string to extract from
     *
     * @return \QueryTranslator\Values\Token|null Extracted token or null if it could not be extracted
     */
    public function extract($string, $position)
    {
        $byteOffset = $this->getByteOffset($string, $position);

        foreach ($this->getExpressionTypeMap() as $expression => $type) {
            $success = preg_match($expression, $string, $matches, 0, $byteOffset);

            if (false === $success) {
                throw new RuntimeException('PCRE regex error code: ' . preg_last_error());
            }

            if (0 === $success) {
                continue;
            }

            return $this->createToken($type, $position, $matches);
        }

        return new Token(
            Tokenizer::TOKEN_BAILOUT,
            mb_substr($string, $position, 1),
            $position
        );
    }

    /**
     * Return the offset of the given $position in the input $string, in bytes.
     *
     * Offset in bytes is needed for preg_match $offset parameter.
     *
     * @param string $string
     * @param int $position
     *
     * @return int
     */
    private function getByteOffset($string, $position)
    {
        return strlen(mb_substr($string, 0, $position));
    }

    /**
     * Create a token object from the given parameters.
     *
     * @param int $type Token type
     * @param int $position Position of the token in the input string
     * @param array $data Regex match data, depends on the type of the token
     *
     * @return \QueryTranslator\Values\Token
     */
    protected function createToken($type, $position, array $data)
    {
        $lexeme = $data['lexeme'];

        if ($type !== Tokenizer::TOKEN_TERM) {
            return new Token($type, $lexeme, $position);
        }

        return $this->createTermToken($position, $data);
    }

    /**
     * Return a map of regular expressions to token types.
     *
     * The returned map must an array where key is a regular expression
     * and value is a corresponding token type.
     *
     * @return array
     */
    abstract protected function getExpressionTypeMap();

    /**
     * Create a term type token by the given parameters.
     *
     * @throw \RuntimeException If token could not be created from the given $matches data
     *
     * @param int $position Position of the token in the input string
     * @param array $data Regex match data, depends on the matched term token
     *
     * @return \QueryTranslator\Values\Token
     */
    abstract protected function createTermToken($position, array $data);
}
