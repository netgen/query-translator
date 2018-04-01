<?php

namespace QueryTranslator\Languages\Galach\TokenExtractor;

use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Token\GroupBegin;
use QueryTranslator\Languages\Galach\Values\Token\Phrase;
use QueryTranslator\Languages\Galach\Values\Token\Word;
use RuntimeException;

/**
 * Text implementation of the Galach token extractor.
 *
 * Supports text related subset of the language features.
 */
final class Text extends TokenExtractor
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
        '/(?<lexeme>\()/Au' => Tokenizer::TOKEN_GROUP_BEGIN,
        '/(?<lexeme>(?<quote>(?<!\\\\)["])(?<phrase>.*?)(?:(?<!\\\\)(?P=quote)))/Aus' => Tokenizer::TOKEN_TERM,
        '/(?<lexeme>(?<word>(?:\\\\\\\\|\\\\ |\\\\\(|\\\\\)|\\\\"|[^"()\s])+?))(?:(?<!\\\\)["]|\(|\)|$|\s)/Au' => Tokenizer::TOKEN_TERM,
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
                    '',
                    // un-backslash special chars
                    preg_replace('/(?:\\\\(\\\\|(["+\-!() ])))/', '$1', $data['word'])
                );
            case isset($data['phrase']):
                $quote = $data['quote'];

                return new Phrase(
                    $lexeme,
                    $position,
                    '',
                    $quote,
                    // un-backslash quote
                    preg_replace('/(?:\\\\([' . $quote . ']))/', '$1', $data['phrase'])
                );
        }

        throw new RuntimeException('Could not extract term token from the given data');
    }

    protected function createGroupBeginToken($position, array $data)
    {
        return new GroupBegin($data['lexeme'], $position, $data['lexeme'], '');
    }
}
