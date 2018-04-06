<?php

namespace QueryTranslator\Tests\Galach\Tokenizer;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Token\GroupBegin as GroupBeginToken;
use QueryTranslator\Languages\Galach\Values\Token\GroupBegin;
use QueryTranslator\Languages\Galach\Values\Token\Phrase as PhraseToken;
use QueryTranslator\Languages\Galach\Values\Token\Range as RangeToken;
use QueryTranslator\Languages\Galach\Values\Token\Tag as TagToken;
use QueryTranslator\Languages\Galach\Values\Token\User as UserToken;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Token;
use QueryTranslator\Values\TokenSequence;

/**
 * Test case for tokenizer using Full token extractor.
 */
class FullTokenizerTest extends TestCase
{
    public function providerForTestTokenize()
    {
        return [
            [
                " \n",
                [
                    new Token(Tokenizer::TOKEN_WHITESPACE, " \n", 0),
                ],
            ],
            [
                'word',
                [
                    new WordToken('word', 0, '', 'word'),
                ],
            ],
            [
                "word\n",
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 4),
                ],
            ],
            [
                'word ',
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 4),
                ],
            ],
            [
                'word(',
                [
                    new WordToken('word', 0, '', 'word'),
                    new GroupBeginToken('(', 4, '(', null),
                ],
            ],
            [
                'word)',
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 4),
                ],
            ],
            [
                'šđčćž',
                [
                    new WordToken('šđčćž', 0, '', 'šđčćž'),
                ],
            ],
            [
                $jajeNaOko = mb_convert_encoding('&#x1F373;', 'UTF-8', 'HTML-ENTITIES'),
                [
                    new WordToken($jajeNaOko, 0, '', $jajeNaOko),
                ],
            ],
            [
                $blah = mb_convert_encoding(
                    '&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;',
                    'UTF-8',
                    'HTML-ENTITIES'
                ),
                [
                    new WordToken($blah, 0, '', $blah),
                ],
            ],
            [
                'word-word',
                [
                    new WordToken('word-word', 0, '', 'word-word'),
                ],
            ],
            [
                "word\nword",
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 4),
                    new WordToken('word', 5, '', 'word'),
                ],
            ],
            [
                'word word',
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 4),
                    new WordToken('word', 5, '', 'word'),
                ],
            ],
            [
                'word\\ word',
                [
                    new WordToken('word\\ word', 0, '', 'word word'),
                ],
            ],
            [
                '[a TO b]',
                [
                    new RangeToken('[a TO b]', 0, '', 'a', 'b', 'inclusive', 'inclusive'),
                ],
            ],
            [
                '[a TO b}',
                [
                    new RangeToken('[a TO b}', 0, '', 'a', 'b', 'inclusive', 'exclusive'),
                ],
            ],
            [
                '{a TO b}',
                [
                    new RangeToken('{a TO b}', 0, '', 'a', 'b', 'exclusive', 'exclusive'),
                ],
            ],
            [
                '{a TO b]',
                [
                    new RangeToken('{a TO b]', 0, '', 'a', 'b', 'exclusive', 'inclusive'),
                ],
            ],
            [
                '"phrase"',
                [
                    new PhraseToken('"phrase"', 0, '', '"', 'phrase'),
                ],
            ],
            [
                '"phrase" "phrase"',
                [
                    new PhraseToken('"phrase"', 0, '', '"', 'phrase'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 8),
                    new PhraseToken('"phrase"', 9, '', '"', 'phrase'),
                ],
            ],
            [
                "\"phrase\nphrase\"",
                [
                    new PhraseToken("\"phrase\nphrase\"", 0, '', '"', "phrase\nphrase"),
                ],
            ],
            [
                "'phrase'",
                [
                    new WordToken("'phrase'", 0, '', "'phrase'"),
                ],
            ],
            [
                "'phrase' 'phrase'",
                [
                    new WordToken("'phrase'", 0, '', "'phrase'"),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 8),
                    new WordToken("'phrase'", 9, '', "'phrase'"),
                ],
            ],
            [
                "'phrase\nphrase'",
                [
                    new WordToken("'phrase", 0, '', "'phrase"),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 7),
                    new WordToken("phrase'", 8, '', "phrase'"),
                ],
            ],
            [
                '"phrase\"phrase"',
                [
                    new PhraseToken('"phrase\"phrase"', 0, '', '"', 'phrase"phrase'),
                ],
            ],
            [
                "'phrase\\'phrase'",
                [
                    new WordToken("'phrase\\'phrase'", 0, '', "'phrase\\'phrase'"),
                ],
            ],
            [
                '"phrase\'phrase"',
                [
                    new PhraseToken('"phrase\'phrase"', 0, '', '"', 'phrase\'phrase'),
                ],
            ],
            [
                "'phrase\"phrase'",
                [
                    new WordToken("'phrase", 0, '', "'phrase"),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 7),
                    new WordToken("phrase'", 8, '', "phrase'"),
                ],
            ],
            [
                '\"not_phrase\"',
                [
                    new WordToken('\"not_phrase\"', 0, '', '"not_phrase"'),
                ],
            ],
            [
                "\\'not_phrase\\'",
                [
                    new WordToken("\\'not_phrase\\'", 0, '', "\\'not_phrase\\'"),
                ],
            ],
            [
                '"phrase + - ! ( ) AND OR NOT \\ phrase"',
                [
                    new PhraseToken(
                        '"phrase + - ! ( ) AND OR NOT \\ phrase"',
                        0,
                        '',
                        '"',
                        'phrase + - ! ( ) AND OR NOT \\ phrase'
                    ),
                ],
            ],
            [
                "'word + - ! ( ) AND OR NOT \\ word'",
                [
                    new WordToken("'word", 0, '', "'word"),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 5),
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 6),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 7),
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 8),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 9),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 10),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 11),
                    new GroupBegin('(', 12, '(', ''),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 13),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 14),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 15),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 16),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 19),
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 20),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 22),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 23),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 26),
                    new WordToken("\\ word'", 27, '', " word'"),
                ],
            ],
            [
                '"phrase \+ \- \! \( \) \AND \OR \NOT \\\\ phrase"',
                [
                    new PhraseToken(
                        '"phrase \+ \- \! \( \) \AND \OR \NOT \\\\ phrase"',
                        0,
                        '',
                        '"',
                        'phrase \+ \- \! \( \) \AND \OR \NOT \\\\ phrase'
                    ),
                ],
            ],
            [
                "'word \\+ \\- \\! \\( \\) \\AND \\OR \\NOT \\\\ word'",
                [
                    new WordToken("'word", 0, '', "'word"),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 5),
                    new WordToken("\\+", 6, '', '+'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 8),
                    new WordToken("\\-", 9, '', '-'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 11),
                    new WordToken("\\!", 12, '', '!'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 14),
                    new WordToken("\\(", 15, '', '('),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 17),
                    new WordToken("\\)", 18, '', ')'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 20),
                    new WordToken("\\AND", 21, '', '\AND'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 25),
                    new WordToken("\\OR", 26, '', '\OR'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 29),
                    new WordToken("\\NOT", 30, '', '\NOT'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 34),
                    new WordToken("\\\\", 35, '', '\\'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 37),
                    new WordToken("word'", 38, '', "word'"),
                ],
            ],
            [
                '#tag',
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                ],
            ],
            [
                '\#tag',
                [
                    new WordToken('\#tag', 0, '', '#tag'),
                ],
            ],
            [
                '#tagšđčćž',
                [
                    new WordToken('#tagšđčćž', 0, '', '#tagšđčćž'),
                ],
            ],
            [
                '#_tag-tag',
                [
                    new TagToken('#_tag-tag', 0, '#', '_tag-tag'),
                ],
            ],
            [
                '#-not-tag',
                [
                    new WordToken('#-not-tag', 0, '', '#-not-tag'),
                ],
            ],
            [
                '#tag+',
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                ],
            ],
            [
                '#tag-',
                [
                    new TagToken('#tag-', 0, '#', 'tag-'),
                ],
            ],
            [
                '#tag!',
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                ],
            ],
            [
                "#tag\n",
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 4),
                ],
            ],
            [
                '#tag ',
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 4),
                ],
            ],
            [
                '#tag(',
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                    new GroupBeginToken('(', 4, '(', null),
                ],
            ],
            [
                '#tag)',
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 4),
                ],
            ],
            [
                '@user',
                [
                    new UserToken('@user', 0, '@', 'user'),
                ],
            ],
            [
                '@user.user',
                [
                    new UserToken('@user.user', 0, '@', 'user.user'),
                ],
            ],
            [
                '\@user',
                [
                    new WordToken('\@user', 0, '', '@user'),
                ],
            ],
            [
                '@useršđčćž',
                [
                    new WordToken('@useršđčćž', 0, '', '@useršđčćž'),
                ],
            ],
            [
                '@_user-user',
                [
                    new UserToken('@_user-user', 0, '@', '_user-user'),
                ],
            ],
            [
                '@-not-user',
                [
                    new WordToken('@-not-user', 0, '', '@-not-user'),
                ],
            ],
            [
                '@user+',
                [
                    new UserToken('@user', 0, '@', 'user'),
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                ],
            ],
            [
                '@user-',
                [
                    new UserToken('@user-', 0, '@', 'user-'),
                ],
            ],
            [
                '@user!',
                [
                    new UserToken('@user', 0, '@', 'user'),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 5),
                ],
            ],
            [
                "@user\n",
                [
                    new UserToken('@user', 0, '@', 'user'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 5),
                ],
            ],
            [
                '@user ',
                [
                    new UserToken('@user', 0, '@', 'user'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 5),
                ],
            ],
            [
                '@user(',
                [
                    new UserToken('@user', 0, '@', 'user'),
                    new GroupBeginToken('(', 5, '(', null),
                ],
            ],
            [
                '@user)',
                [
                    new UserToken('@user', 0, '@', 'user'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                ],
            ],
            [
                'domain:',
                [
                    new WordToken('domain:', 0, '', 'domain:'),
                ],
            ],
            [
                'some.domain:',
                [
                    new WordToken('some.domain:', 0, '', 'some.domain:'),
                ],
            ],
            [
                'domain:domain:',
                [
                    new WordToken('domain:domain:', 0, 'domain', 'domain:'),
                ],
            ],
            [
                'some.domain:some.domain:',
                [
                    new WordToken('some.domain:some.domain:', 0, 'some.domain', 'some.domain:'),
                ],
            ],
            [
                'domain:domain:domain:domain',
                [
                    new WordToken('domain:domain:domain:domain', 0, 'domain', 'domain:domain:domain'),
                ],
            ],
            [
                'domain\:',
                [
                    new WordToken('domain\:', 0, '', 'domain:'),
                ],
            ],
            [
                'domain\::',
                [
                    new WordToken('domain\::', 0, '', 'domain::'),
                ],
            ],
            [
                'domain:word',
                [
                    new WordToken('domain:word', 0, 'domain', 'word'),
                ],
            ],
            [
                'domain\:word',
                [
                    new WordToken('domain\:word', 0, '', 'domain:word'),
                ],
            ],
            [
                'domain:"phrase"',
                [
                    new PhraseToken('domain:"phrase"', 0, 'domain', '"', 'phrase'),
                ],
            ],
            [
                'some.domain:"phrase"',
                [
                    new PhraseToken('some.domain:"phrase"', 0, 'some.domain', '"', 'phrase'),
                ],
            ],
            [
                'domain\:"phrase"',
                [
                    new WordToken('domain\:', 0, '', 'domain:'),
                    new PhraseToken('"phrase"', 8, '', '"', 'phrase'),
                ],
            ],
            [
                'domain:(one)',
                [
                    new GroupBeginToken('domain:(', 0, '(', 'domain'),
                    new WordToken('one', 8, '', 'one'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 11),
                ],
            ],
            [
                'some.domain:(one)',
                [
                    new GroupBeginToken('some.domain:(', 0, '(', 'some.domain'),
                    new WordToken('one', 13, '', 'one'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 16),
                ],
            ],
            [
                'one AND two',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 3),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 7),
                    new WordToken('two', 8, '', 'two'),
                ],
            ],
            [
                'one && two',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 3),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, '&&', 4),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 6),
                    new WordToken('two', 7, '', 'two'),
                ],
            ],
            [
                'one OR two',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 3),
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 6),
                    new WordToken('two', 7, '', 'two'),
                ],
            ],
            [
                'one || two',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 3),
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, '||', 4),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 6),
                    new WordToken('two', 7, '', 'two'),
                ],
            ],
            [
                'one NOT two',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 3),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 7),
                    new WordToken('two', 8, '', 'two'),
                ],
            ],
            [
                'AND',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                ],
            ],
            [
                'ANDword',
                [
                    new WordToken('ANDword', 0, '', 'ANDword'),
                ],
            ],
            [
                'wordAND',
                [
                    new WordToken('wordAND', 0, '', 'wordAND'),
                ],
            ],
            [
                'AND+',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 3),
                ],
            ],
            [
                'AND\+',
                [
                    new WordToken('AND\+', 0, '', 'AND+'),
                ],
            ],
            [
                '+AND',
                [
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 1),
                ],
            ],
            [
                'AND-',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 3),
                ],
            ],
            [
                'AND\-',
                [
                    new WordToken('AND\-', 0, '', 'AND-'),
                ],
            ],
            [
                '-AND',
                [
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 1),
                ],
            ],
            [
                'AND!',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 3),
                ],
            ],
            [
                'AND\!',
                [
                    new WordToken('AND\!', 0, '', 'AND!'),
                ],
            ],
            [
                '!AND',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 1),
                ],
            ],
            [
                "AND\n",
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 3),
                ],
            ],
            [
                'AND ',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 3),
                ],
            ],
            [
                'AND(',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new GroupBeginToken('(', 3, '(', null),
                ],
            ],
            [
                'AND)',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 3),
                ],
            ],
            [
                'ORword',
                [
                    new WordToken('ORword', 0, '', 'ORword'),
                ],
            ],
            [
                'ORword',
                [
                    new WordToken('ORword', 0, '', 'ORword'),
                ],
            ],
            [
                'OR',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                ],
            ],
            [
                'OR+',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 2),
                ],
            ],
            [
                'OR\+',
                [
                    new WordToken('OR\+', 0, '', 'OR+'),
                ],
            ],
            [
                '+OR',
                [
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 1),
                ],
            ],
            [
                'OR-',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 2),
                ],
            ],
            [
                'OR\+',
                [
                    new WordToken('OR\+', 0, '', 'OR+'),
                ],
            ],
            [
                '-OR',
                [
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 1),
                ],
            ],
            [
                'OR!',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 2),
                ],
            ],
            [
                'OR\!',
                [
                    new WordToken('OR\!', 0, '', 'OR!'),
                ],
            ],
            [
                '!OR',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 1),
                ],
            ],
            [
                "OR\n",
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 2),
                ],
            ],
            [
                'OR ',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 2),
                ],
            ],
            [
                'OR(',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new GroupBeginToken('(', 2, '(', null),
                ],
            ],
            [
                'OR)',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 2),
                ],
            ],
            [
                'NOT',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                ],
            ],
            [
                'NOTword',
                [
                    new WordToken('NOTword', 0, '', 'NOTword'),
                ],
            ],
            [
                'wordNOT',
                [
                    new WordToken('wordNOT', 0, '', 'wordNOT'),
                ],
            ],
            [
                'NOT+',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 3),
                ],
            ],
            [
                '+NOT',
                [
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 1),
                ],
            ],
            [
                'NOT-',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 3),
                ],
            ],
            [
                '-NOT',
                [
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 1),
                ],
            ],
            [
                'NOT!',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 3),
                ],
            ],
            [
                '!NOT',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 1),
                ],
            ],
            [
                "NOT\n",
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 3),
                ],
            ],
            [
                'NOT ',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 3),
                ],
            ],
            [
                'NOT(',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new GroupBeginToken('(', 3, '(', null),
                ],
            ],
            [
                'NOT)',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 3),
                ],
            ],
            [
                '+',
                [
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                ],
            ],
            [
                '++',
                [
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 1),
                ],
            ],
            [
                '-',
                [
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                ],
            ],
            [
                '--',
                [
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 1),
                ],
            ],
            [
                '!',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                ],
            ],
            [
                '!!',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 1),
                ],
            ],
            [
                '+word',
                [
                    new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    new WordToken('word', 1, '', 'word'),
                ],
            ],
            [
                '-word',
                [
                    new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    new WordToken('word', 1, '', 'word'),
                ],
            ],
            [
                '!word',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                    new WordToken('word', 1, '', 'word'),
                ],
            ],
            [
                '(word',
                [
                    new GroupBeginToken('(', 0, '(', null),
                    new WordToken('word', 1, '', 'word'),
                ],
            ],
            [
                ')word',
                [
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 0),
                    new WordToken('word', 1, '', 'word'),
                ],
            ],
            [
                'word+',
                [
                    new WordToken('word+', 0, '', 'word+'),
                ],
            ],
            [
                'word-',
                [
                    new WordToken('word-', 0, '', 'word-'),
                ],
            ],
            [
                'word!',
                [
                    new WordToken('word!', 0, '', 'word!'),
                ],
            ],
            [
                'word(',
                [
                    new WordToken('word', 0, '', 'word'),
                    new GroupBeginToken('(', 4, '(', null),
                ],
            ],
            [
                'word)',
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 4),
                ],
            ],
            [
                'one+two+',
                [
                    new WordToken('one+two+', 0, '', 'one+two+'),
                ],
            ],
            [
                'one-two-',
                [
                    new WordToken('one-two-', 0, '', 'one-two-'),
                ],
            ],
            [
                'one!two!',
                [
                    new WordToken('one!two!', 0, '', 'one!two!'),
                ],
            ],
            [
                'one(two(',
                [
                    new WordToken('one', 0, '', 'one'),
                    new GroupBeginToken('(', 3, '(', null),
                    new WordToken('two', 4, '', 'two'),
                    new GroupBeginToken('(', 7, '(', null),
                ],
            ],
            [
                'one)two)',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 3),
                    new WordToken('two', 4, '', 'two'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 7),
                ],
            ],
            [
                'word\+',
                [
                    new WordToken('word\+', 0, '', 'word+'),
                ],
            ],
            [
                'word\-',
                [
                    new WordToken('word\-', 0, '', 'word-'),
                ],
            ],
            [
                'word\!',
                [
                    new WordToken('word\!', 0, '', 'word!'),
                ],
            ],
            [
                'word\(',
                [
                    new WordToken('word\(', 0, '', 'word('),
                ],
            ],
            [
                'word\)',
                [
                    new WordToken('word\)', 0, '', 'word)'),
                ],
            ],
            [
                '\+word',
                [
                    new WordToken('\+word', 0, '', '+word'),
                ],
            ],
            [
                '\-word',
                [
                    new WordToken('\-word', 0, '', '-word'),
                ],
            ],
            [
                '\!word',
                [
                    new WordToken('\!word', 0, '', '!word'),
                ],
            ],
            [
                '\(word',
                [
                    new WordToken('\(word', 0, '', '(word'),
                ],
            ],
            [
                '\)word',
                [
                    new WordToken('\)word', 0, '', ')word'),
                ],
            ],
            [
                'one\+two\+',
                [
                    new WordToken('one\+two\+', 0, '', 'one+two+'),
                ],
            ],
            [
                'one\-two\-',
                [
                    new WordToken('one\-two\-', 0, '', 'one-two-'),
                ],
            ],
            [
                'one\!two\!',
                [
                    new WordToken('one\!two\!', 0, '', 'one!two!'),
                ],
            ],
            [
                'one\(two\(',
                [
                    new WordToken('one\(two\(', 0, '', 'one(two('),
                ],
            ],
            [
                'one\)two\)',
                [
                    new WordToken('one\)two\)', 0, '', 'one)two)'),
                ],
            ],
            [
                'one\\\\\)two\\\\\(one\\\\\+two\\\\\-one\\\\\!two',
                [
                    new WordToken(
                        'one\\\\\)two\\\\\(one\\\\\+two\\\\\-one\\\\\!two',
                        0,
                        '',
                        'one\)two\(one\+two\-one\!two'
                    ),
                ],
            ],
            [
                'one\\\\)two\\\\(one\\\\+two\\\\-one\\\\!two',
                [
                    new WordToken(
                        'one\\\\',
                        0,
                        '',
                        'one\\'
                    ),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                    new WordToken(
                        'two\\\\',
                        6,
                        '',
                        'two\\'
                    ),
                    new GroupBeginToken('(', 11, '(', null),
                    new WordToken(
                        'one\\\\+two\\\\-one\\\\!two',
                        12,
                        '',
                        'one\+two\-one\!two'
                    ),
                ],
            ],
            [
                'one+two-one!two',
                [
                    new WordToken(
                        'one+two-one!two',
                        0,
                        '',
                        'one+two-one!two'
                    ),
                ],
            ],
            [
                'one\\\'two',
                [
                    new WordToken('one\\\'two', 0, '', "one\\'two"),
                ],
            ],
            [
                'one\\"two',
                [
                    new WordToken('one\\"two', 0, '', 'one"two'),
                ],
            ],
            [
                '\\',
                [
                    new WordToken('\\', 0, '', '\\'),
                ],
            ],
            [
                'one\\two',
                [
                    new WordToken('one\\two', 0, '', 'one\\two'),
                ],
            ],
            [
                'one\\\\+\\-\\!\\(\\)two',
                [
                    new WordToken('one\\\\+\\-\\!\\(\\)two', 0, '', 'one\\+-!()two'),
                ],
            ],
            [
                '\\\\',
                [
                    new WordToken('\\\\', 0, '', '\\'),
                ],
            ],
            [
                '(type:)',
                [
                    new GroupBeginToken('(', 0, '(', null),
                    new WordToken('type:', 1, '', 'type:'),
                    new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                ],
            ],
            [
                'type: AND',
                [
                    new WordToken('type:', 0, '', 'type:'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 5),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 6),
                ],
            ],
            [
                "word'",
                [
                    new WordToken("word'", 0, '', "word'"),
                ],
            ],
            [
                'one\'two',
                [
                    new WordToken("one'two", 0, '', "one'two"),
                ],
            ],
            [
                "AND'",
                [
                    new WordToken("AND'", 0, '', "AND'"),
                ],
            ],
            [
                "OR'",
                [
                    new WordToken("OR'", 0, '', "OR'"),
                ],
            ],
            [
                "NOT'",
                [
                    new WordToken("NOT'", 0, '', "NOT'"),
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestTokenize
     *
     * @param string $string
     * @param \QueryTranslator\Values\Token[] $expectedTokens
     */
    public function testTokenize($string, array $expectedTokens)
    {
        $tokenExtractor = $this->getTokenExtractor();
        $tokenizer = new Tokenizer($tokenExtractor);

        $tokenSequence = $tokenizer->tokenize($string);

        $this->assertInstanceOf(TokenSequence::class, $tokenSequence);
        $this->assertEquals($expectedTokens, $tokenSequence->tokens);
        $this->assertEquals($string, $tokenSequence->source);
    }

    public function providerForTestTokenizeNotRecognized()
    {
        return [
            [
                (
                    $blah = mb_convert_encoding(
                        '&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;',
                        'UTF-8',
                        'HTML-ENTITIES'
                    )
                ) . '"',
                [
                    new WordToken($blah, 0, '', $blah),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 7),
                ],
            ],
            [
                '"' . $blah,
                [
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 0),
                    new WordToken($blah, 1, '', $blah),
                ],
            ],
            [
                'word"',
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 4),
                ],
            ],
            [
                'one"two',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 3),
                    new WordToken('two', 4, '', 'two'),
                ],
            ],
            [
                'šđ"čćž',
                [
                    new WordToken('šđ', 0, '', 'šđ'),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 2),
                    new WordToken('čćž', 3, '', 'čćž'),
                ],
            ],
            [
                'AND"',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 3),
                ],
            ],
            [
                'OR"',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 2),
                ],
            ],
            [
                'NOT"',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 3),
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestTokenizeNotRecognized
     *
     * @param string $string
     * @param \QueryTranslator\Values\Token[] $expectedTokens
     */
    public function testTokenizeNotRecognized($string, array $expectedTokens)
    {
        $tokenExtractor = $this->getTokenExtractor();
        $tokenizer = new Tokenizer($tokenExtractor);

        $tokenSequence = $tokenizer->tokenize($string);

        $this->assertInstanceOf(TokenSequence::class, $tokenSequence);
        $this->assertEquals($expectedTokens, $tokenSequence->tokens);
        $this->assertEquals($string, $tokenSequence->source);
    }

    /**
     * @return \QueryTranslator\Languages\Galach\TokenExtractor
     */
    protected function getTokenExtractor()
    {
        return new TokenExtractor\Full();
    }
}
