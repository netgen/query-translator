<?php

namespace QueryTranslator\Tests\Galach;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Token\Phrase as PhraseToken;
use QueryTranslator\Languages\Galach\Values\Token\Tag as TagToken;
use QueryTranslator\Languages\Galach\Values\Token\User as UserToken;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Token;
use QueryTranslator\Values\TokenSequence;

/**
 * Functional test case for tokenizer implementation.
 */
class TokenizerTest extends TestCase
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 4),
                ],
            ],
            [
                'word)',
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 4),
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
                    new PhraseToken("'phrase'", 0, '', "'", 'phrase'),
                ],
            ],
            [
                "'phrase' 'phrase'",
                [
                    new PhraseToken("'phrase'", 0, '', "'", 'phrase'),
                    new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 8),
                    new PhraseToken("'phrase'", 9, '', "'", 'phrase'),
                ],
            ],
            [
                "'phrase\nphrase'",
                [
                    new PhraseToken("'phrase\nphrase'", 0, '', "'", "phrase\nphrase"),
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
                    new PhraseToken("'phrase\\'phrase'", 0, '', "'", "phrase'phrase"),
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
                    new PhraseToken("'phrase\"phrase'", 0, '', "'", 'phrase"phrase'),
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
                    new WordToken("\\'not_phrase\\'", 0, '', "'not_phrase'"),
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
                "'phrase + - ! ( ) AND OR NOT \\ phrase'",
                [
                    new PhraseToken(
                        "'phrase + - ! ( ) AND OR NOT \\ phrase'",
                        0,
                        '',
                        "'",
                        'phrase + - ! ( ) AND OR NOT \\ phrase'
                    ),
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
                "'phrase \\+ \\- \\! \\( \\) \\AND \\OR \\NOT \\\\ phrase'",
                [
                    new PhraseToken(
                        "'phrase \\+ \\- \\! \\( \\) \\AND \\OR \\NOT \\\\ phrase'",
                        0,
                        '',
                        "'",
                        'phrase \\+ \\- \\! \\( \\) \\AND \\OR \\NOT \\\\ phrase'
                    ),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 4),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 4),
                ],
            ],
            [
                '#tag)',
                [
                    new TagToken('#tag', 0, '#', 'tag'),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 4),
                ],
            ],
            [
                '@user',
                [
                    new UserToken('@user', 0, '@', 'user'),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 5),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 5),
                ],
            ],
            [
                '@user)',
                [
                    new UserToken('@user', 0, '@', 'user'),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 5),
                ],
            ],
            [
                'domain:',
                [
                    new WordToken('domain:', 0, '', 'domain:'),
                ],
            ],
            [
                'domain:domain:',
                [
                    new WordToken('domain:domain:', 0, 'domain', 'domain:'),
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
                'domain\:"phrase"',
                [
                    new WordToken('domain\:', 0, '', 'domain:'),
                    new PhraseToken('"phrase"', 8, '', '"', 'phrase'),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 3),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 1),
                ],
            ],
            [
                'AND-',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 3),
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
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 0),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 3),
                ],
            ],
            [
                'AND)',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 3),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 2),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 1),
                ],
            ],
            [
                'OR-',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 2),
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
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 0),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 2),
                ],
            ],
            [
                'OR)',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 2),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 3),
                ],
            ],
            [
                '+NOT',
                [
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 0),
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 1),
                ],
            ],
            [
                'NOT-',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 3),
                ],
            ],
            [
                '-NOT',
                [
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 0),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 3),
                ],
            ],
            [
                'NOT)',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 3),
                ],
            ],
            [
                '+',
                [
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 0),
                ],
            ],
            [
                '++',
                [
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 0),
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 1),
                ],
            ],
            [
                '-',
                [
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 0),
                ],
            ],
            [
                '--',
                [
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 0),
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 1),
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
                    new Token(Tokenizer::TOKEN_INCLUDE, '+', 0),
                    new WordToken('word', 1, '', 'word'),
                ],
            ],
            [
                '-word',
                [
                    new Token(Tokenizer::TOKEN_EXCLUDE, '-', 0),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 0),
                    new WordToken('word', 1, '', 'word'),
                ],
            ],
            [
                ')word',
                [
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 0),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 4),
                ],
            ],
            [
                'word)',
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 4),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 3),
                    new WordToken('two', 4, '', 'two'),
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 7),
                ],
            ],
            [
                'one)two)',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 3),
                    new WordToken('two', 4, '', 'two'),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 7),
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
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 5),
                    new WordToken(
                        'two\\\\',
                        6,
                        '',
                        'two\\'
                    ),
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 11),
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
                    new WordToken('one\\\'two', 0, '', "one'two"),
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
                    new Token(Tokenizer::TOKEN_GROUP_LEFT_DELIMITER, '(', 0),
                    new WordToken('type:', 1, '', 'type:'),
                    new Token(Tokenizer::TOKEN_GROUP_RIGHT_DELIMITER, ')', 6),
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
        ];
    }

    /**
     * @dataProvider providerForTestTokenize
     *
     * @param string $string
     * @param array $expectedTokens
     */
    public function testTokenize($string, array $expectedTokens)
    {
        $tokenExtractor = new TokenExtractor\Full();
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
                '"' . (
                    $blah = mb_convert_encoding(
                        '&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;',
                        'UTF-8',
                        'HTML-ENTITIES'
                    )
                ),
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
                "word'",
                [
                    new WordToken('word', 0, '', 'word'),
                    new Token(Tokenizer::TOKEN_BAILOUT, "'", 4),
                ],
            ],
            [
                'one\'two',
                [
                    new WordToken('one', 0, '', 'one'),
                    new Token(Tokenizer::TOKEN_BAILOUT, "'", 3),
                    new WordToken('two', 4, '', 'two'),
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
                "AND'",
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    new Token(Tokenizer::TOKEN_BAILOUT, "'", 3),
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
                "OR'",
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    new Token(Tokenizer::TOKEN_BAILOUT, "'", 2),
                ],
            ],
            [
                'NOT"',
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_BAILOUT, '"', 3),
                ],
            ],
            [
                "NOT'",
                [
                    new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    new Token(Tokenizer::TOKEN_BAILOUT, "'", 3),
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestTokenizeNotRecognized
     *
     * @param string $string
     * @param array $expectedTokens
     */
    public function testTokenizeNotRecognized($string, array $expectedTokens)
    {
        $tokenExtractor = new TokenExtractor\Full();
        $tokenizer = new Tokenizer($tokenExtractor);

        $tokenSequence = $tokenizer->tokenize($string);

        $this->assertInstanceOf(TokenSequence::class, $tokenSequence);
        $this->assertEquals($expectedTokens, $tokenSequence->tokens);
        $this->assertEquals($string, $tokenSequence->source);
    }
}
