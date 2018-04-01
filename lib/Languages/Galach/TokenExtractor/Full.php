<?php

namespace QueryTranslator\Languages\Galach\TokenExtractor;

use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Token\Phrase;
use QueryTranslator\Languages\Galach\Values\Token\Tag;
use QueryTranslator\Languages\Galach\Values\Token\User;
use QueryTranslator\Languages\Galach\Values\Token\Word;
use RuntimeException;

/**
 * Full implementation of the Galach token extractor.
 *
 * Supports all features of the language.
 */
final class Full extends TokenExtractor
{
    /**
     * Map of regex expressions to Token types.
     *
     * @var array
     */
    private static $expressionTypeMap = [
        '/(?<lexeme>[\s]+)/Au' => Tokenizer::TOKEN_WHITESPACE,
        '/(?<lexeme>\+)/Au' => Tokenizer::TOKEN_MANDATORY,
        '/(?<lexeme>-)/Au' => Tokenizer::TOKEN_PROHIBITED,
        '/(?<lexeme>!)/Au' => Tokenizer::TOKEN_LOGICAL_NOT_2,
        '/(?<lexeme>\))/Au' => Tokenizer::TOKEN_GROUP_END,
        '/(?<lexeme>NOT)(?:[\s"()+\-!]|$)/Au' => Tokenizer::TOKEN_LOGICAL_NOT,
        '/(?<lexeme>(?:AND|&&))(?:[\s"()+\-!]|$)/Au' => Tokenizer::TOKEN_LOGICAL_AND,
        '/(?<lexeme>(?:OR|\|\|))(?:[\s"()+\-!]|$)/Au' => Tokenizer::TOKEN_LOGICAL_OR,
        '/(?<lexeme>(?:(?<domain>[a-zA-Z_][a-zA-Z0-9_\-.]*):)?(?<delimiter>\())/Au' => Tokenizer::TOKEN_GROUP_BEGIN,
        '/(?<lexeme>(?:(?<marker>(?<!\\\\)\#)(?<tag>[a-zA-Z0-9_][a-zA-Z0-9_\-.]*)))(?:[\s"()+!]|$)/Au' => Tokenizer::TOKEN_TERM,
        '/(?<lexeme>(?:(?<marker>(?<!\\\\)@)(?<user>[a-zA-Z0-9_][a-zA-Z0-9_\-.]*)))(?:[\s"()+!]|$)/Au' => Tokenizer::TOKEN_TERM,
        '/(?<lexeme>(?:(?<domain>[a-zA-Z_][a-zA-Z0-9_\-.]*):)?(?<quote>(?<!\\\\)["])(?<phrase>.*?)(?:(?<!\\\\)(?P=quote)))/Aus' => Tokenizer::TOKEN_TERM,
        '/(?<lexeme>(?:(?<domain>[a-zA-Z_][a-zA-Z0-9_\-.]*):)?(?<word>(?:\\\\\\\\|\\\\ |\\\\\(|\\\\\)|\\\\"|[^"()\s])+?))(?:(?<!\\\\)["]|\(|\)|$|\s)/Au' => Tokenizer::TOKEN_TERM,
    ];

    protected function getExpressionTypeMap()
    {
        return self::$expressionTypeMap;
    }

    protected function createTermToken($position, array $data)
    {
        $lexeme = $data['lexeme'];

        switch (true) {
            case isset($data['word']):
                return new Word(
                    $lexeme,
                    $position,
                    $data['domain'],
                    // un-backslash special characters
                    preg_replace('/(?:\\\\(\\\\|(["+\-!():#@ ])))/', '$1', $data['word'])
                );
            case isset($data['phrase']):
                $quote = $data['quote'];

                return new Phrase(
                    $lexeme,
                    $position,
                    $data['domain'],
                    $quote,
                    // un-backslash quote
                    preg_replace('/(?:\\\\([' . $quote . ']))/', '$1', $data['phrase'])
                );
            case isset($data['tag']):
                return new Tag(
                    $lexeme,
                    $position,
                    $data['marker'],
                    $data['tag']
                );
            case isset($data['user']):
                return new User(
                    $lexeme,
                    $position,
                    $data['marker'],
                    $data['user']
                );
        }

        throw new RuntimeException('Could not extract term token from the given data');
    }
}
