<?php

namespace QueryTranslator\Languages\Galach;

use QueryTranslator\Languages\Galach\Values\Token\GroupBegin;
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
     * @return \QueryTranslator\Values\Token Extracted token
     */
    final public function extract($string, $position)
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
     * Return a map of regular expressions to token types.
     *
     * The returned map must be an array where key is a regular expression
     * and value is a corresponding token type. Regular expression must define
     * named capturing group 'lexeme' that identifies part of the input string
     * recognized as token.
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

    /**
     * Create a token object from the given parameters.
     *
     * @param int $type Token type
     * @param int $position Position of the token in the input string
     * @param array $data Regex match data, depends on the type of the token
     *
     * @return \QueryTranslator\Values\Token
     */
    private function createToken($type, $position, array $data)
    {
        if ($type === Tokenizer::TOKEN_GROUP_BEGIN) {
            return $this->createGroupBeginToken($position, $data);
        }

        if ($type === Tokenizer::TOKEN_TERM) {
            return $this->createTermToken($position, $data);
        }

        return new Token($type, $data['lexeme'], $position);
    }

    /**
     * Create an instance of Group token by the given parameters.
     *
     * @param $position
     * @param array $data
     *
     * @return \QueryTranslator\Values\Token
     */
    protected function createGroupBeginToken($position, array $data)
    {
        return new GroupBegin($data['lexeme'], $position, $data['delimiter'], $data['domain']);
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
}
