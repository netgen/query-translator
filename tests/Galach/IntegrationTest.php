<?php

namespace QueryTranslator\Tests\Galach;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Generators;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Node\Group;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited;
use QueryTranslator\Languages\Galach\Values\Node\Query;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\GroupBegin as GroupBeginToken;
use QueryTranslator\Languages\Galach\Values\Token\Phrase as PhraseToken;
use QueryTranslator\Languages\Galach\Values\Token\Tag as TagToken;
use QueryTranslator\Languages\Galach\Values\Token\User as UserToken;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Correction;
use QueryTranslator\Values\SyntaxTree;
use QueryTranslator\Values\Token;
use QueryTranslator\Values\TokenSequence;

/**
 * Tests integration of language components.
 *
 *  - tokenization of the query string into a sequence of tokens
 *  - parsing the token sequence into a syntax tree
 *  - generating the result by traversing the syntax tree
 */
class IntegrationTest extends TestCase
{
    public function providerForTestQuery()
    {
        return [
            [
                '',
                [],
                new Query([]),
            ],
            [
                'one',
                [
                    $token = new WordToken('one', 0, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token),
                    ]
                ),
            ],
            [
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new WordToken('two', 4, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token2),
                    ]
                ),
            ],
            [
                'one AND two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new WordToken('two', 8, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one OR two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new WordToken('two', 7, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token1),
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one OR two AND three',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new WordToken('two', 7, '', 'two'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 11),
                    $token5 = new WordToken('three', 15, '', 'three'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token1),
                            new LogicalAnd(
                                new Term($token3),
                                new Term($token5),
                                $token4
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one AND two OR three',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new WordToken('two', 8, '', 'two'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 12),
                    $token5 = new WordToken('three', 15, '', 'three'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new LogicalAnd(
                                new Term($token1),
                                new Term($token3),
                                $token2
                            ),
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
            ],
            [
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new WordToken('one', 4, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token2),
                            $token1
                        ),
                    ]
                ),
            ],
            [
                'one NOT two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new WordToken('two', 8, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one AND NOT two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new WordToken('two', 12, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token4),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one OR NOT two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 7),
                    $token4 = new WordToken('two', 11, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token4),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token2),
                            $token1
                        ),
                    ]
                ),
            ],
            [
                'one !two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new WordToken('two', 5, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one AND !two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 8),
                    $token4 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token4),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one OR !two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 7),
                    $token4 = new WordToken('two', 8, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token4),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                '(one two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new WordToken('two', 5, '', 'two'),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 8),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new Term($token2),
                                new Term($token3),
                            ],
                            $token1,
                            $token4
                        ),
                    ]
                ),
            ],
            [
                '(one AND two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new WordToken('two', 9, '', 'two'),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 12),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalAnd(
                                    new Term($token2),
                                    new Term($token4),
                                    $token3
                                ),
                            ],
                            $token1,
                            $token5
                        ),
                    ]
                ),
            ],
            [
                '(NOT one OR two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 1),
                    $token3 = new WordToken('one', 5, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 9),
                    $token5 = new WordToken('two', 12, '', 'two'),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 15),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalOr(
                                    new LogicalNot(
                                        new Term($token3),
                                        $token2
                                    ),
                                    new Term($token5),
                                    $token4
                                ),
                            ],
                            $token1,
                            $token6
                        ),
                    ]
                ),
            ],
            [
                'one AND (two OR three)',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new GroupBeginToken('(', 8, '(', null),
                    $token4 = new WordToken('two', 9, '', 'two'),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 13),
                    $token6 = new WordToken('three', 16, '', 'three'),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 21),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new Group(
                                [
                                    new LogicalOr(
                                        new Term($token4),
                                        new Term($token6),
                                        $token5
                                    ),
                                ],
                                $token3,
                                $token7
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                '((one) AND (two AND (three OR four five)))',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 7),
                    $token6 = new GroupBeginToken('(', 11, '(', null),
                    $token7 = new WordToken('two', 12, '', 'two'),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 16),
                    $token9 = new GroupBeginToken('(', 20, '(', null),
                    $token10 = new WordToken('three', 21, '', 'three'),
                    $token11 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 27),
                    $token12 = new WordToken('four', 30, '', 'four'),
                    $token13 = new WordToken('five', 35, '', 'five'),
                    $token14 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 39),
                    $token15 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 40),
                    $token16 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 41),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalAnd(
                                    new Group(
                                        [
                                            new Term($token3),
                                        ],
                                        $token2,
                                        $token4
                                    ),
                                    new Group(
                                        [
                                            new LogicalAnd(
                                                new Term($token7),
                                                new Group(
                                                    [
                                                        new LogicalOr(
                                                            new Term($token10),
                                                            new Term($token12),
                                                            $token11
                                                        ),
                                                        new Term($token13),
                                                    ],
                                                    $token9,
                                                    $token14
                                                ),
                                                $token8
                                            ),
                                        ],
                                        $token6,
                                        $token15
                                    ),
                                    $token5
                                ),
                            ],
                            $token1,
                            $token16
                        ),
                    ]
                ),
            ],
            [
                '((one) (two OR three))',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                    $token5 = new GroupBeginToken('(', 7, '(', null),
                    $token6 = new WordToken('two', 8, '', 'two'),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 12),
                    $token8 = new WordToken('three', 15, '', 'three'),
                    $token9 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 20),
                    $token10 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 21),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new Group(
                                    [
                                        new Term($token3),
                                    ],
                                    $token2,
                                    $token4
                                ),
                                new Group(
                                    [
                                        new LogicalOr(
                                            new Term($token6),
                                            new Term($token8),
                                            $token7
                                        ),
                                    ],
                                    $token5,
                                    $token9
                                ),
                            ],
                            $token1,
                            $token10
                        ),
                    ]
                ),
            ],
            [
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token2),
                            $token1
                        ),
                    ]
                ),
            ],
            [
                '+one AND +two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 9),
                    $token5 = new WordToken('two', 10, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Mandatory(
                                new Term($token2),
                                $token1
                            ),
                            new Mandatory(
                                new Term($token5),
                                $token4
                            ),
                            $token3
                        ),
                    ]
                ),
            ],
            [
                '+one OR +two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token5 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Mandatory(
                                new Term($token2),
                                $token1
                            ),
                            new Mandatory(
                                new Term($token5),
                                $token4
                            ),
                            $token3
                        ),
                    ]
                ),
            ],
            [
                '+one OR +two AND +three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token5 = new WordToken('two', 9, '', 'two'),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 13),
                    $token7 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 17),
                    $token8 = new WordToken('three', 18, '', 'three'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Mandatory(
                                new Term($token2),
                                $token1
                            ),
                            new LogicalAnd(
                                new Mandatory(
                                    new Term($token5),
                                    $token4
                                ),
                                new Mandatory(
                                    new Term($token8),
                                    $token7
                                ),
                                $token6
                            ),
                            $token3
                        ),
                    ]
                ),
            ],
            [
                '+one AND +two OR +three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 9),
                    $token5 = new WordToken('two', 10, '', 'two'),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 14),
                    $token7 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 17),
                    $token8 = new WordToken('three', 18, '', 'three'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new LogicalAnd(
                                new Mandatory(
                                    new Term($token2),
                                    $token1
                                ),
                                new Mandatory(
                                    new Term($token5),
                                    $token4
                                ),
                                $token3
                            ),
                            new Mandatory(
                                new Term($token8),
                                $token7
                            ),
                            $token6
                        ),
                    ]
                ),
            ],
            [
                '+(one)',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Group(
                                [
                                    new Term($token3),
                                ],
                                $token2,
                                $token4
                            ),
                            $token1
                        ),
                    ]
                ),
            ],
            [
                '-one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Term($token2),
                            $token1
                        ),
                    ]
                ),
            ],
            [
                '-one AND -two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 9),
                    $token5 = new WordToken('two', 10, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Prohibited(
                                new Term($token2),
                                $token1
                            ),
                            new Prohibited(
                                new Term($token5),
                                $token4
                            ),
                            $token3
                        ),
                    ]
                ),
            ],
            [
                '-one OR -two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 8),
                    $token5 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Prohibited(
                                new Term($token2),
                                $token1
                            ),
                            new Prohibited(
                                new Term($token5),
                                $token4
                            ),
                            $token3
                        ),
                    ]
                ),
            ],
            [
                '-one OR -two AND -three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 8),
                    $token5 = new WordToken('two', 9, '', 'two'),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 13),
                    $token7 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 17),
                    $token8 = new WordToken('three', 18, '', 'three'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Prohibited(
                                new Term($token2),
                                $token1
                            ),
                            new LogicalAnd(
                                new Prohibited(
                                    new Term($token5),
                                    $token4
                                ),
                                new Prohibited(
                                    new Term($token8),
                                    $token7
                                ),
                                $token6
                            ),
                            $token3
                        ),
                    ]
                ),
            ],
            [
                '-one AND -two OR -three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 9),
                    $token5 = new WordToken('two', 10, '', 'two'),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 14),
                    $token7 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 17),
                    $token8 = new WordToken('three', 18, '', 'three'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new LogicalAnd(
                                new Prohibited(
                                    new Term($token2),
                                    $token1
                                ),
                                new Prohibited(
                                    new Term($token5),
                                    $token4
                                ),
                                $token3
                            ),
                            new Prohibited(
                                new Term($token8),
                                $token7
                            ),
                            $token6
                        ),
                    ]
                ),
            ],
            [
                '-(one)',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Group(
                                [
                                    new Term($token3),
                                ],
                                $token2,
                                $token4
                            ),
                            $token1
                        ),
                    ]
                ),
            ],
            [
                '(one OR +two three)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token5 = new WordToken('two', 9, '', 'two'),
                    $token6 = new WordToken('three', 13, '', 'three'),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 18),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalOr(
                                    new Term($token2),
                                    new Mandatory(
                                        new Term($token5),
                                        $token4
                                    ),
                                    $token3
                                ),
                                new Term($token6),
                            ],
                            $token1,
                            $token7
                        ),
                    ]
                ),
            ],
            [
                '(one OR -two three)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 8),
                    $token5 = new WordToken('two', 9, '', 'two'),
                    $token6 = new WordToken('three', 13, '', 'three'),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 18),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalOr(
                                    new Term($token2),
                                    new Prohibited(
                                        new Term($token5),
                                        $token4
                                    ),
                                    $token3
                                ),
                                new Term($token6),
                            ],
                            $token1,
                            $token7
                        ),
                    ]
                ),
            ],
            [
                '((one))',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new Group(
                                    [
                                        new Term($token3),
                                    ],
                                    $token2,
                                    $token4
                                ),
                            ],
                            $token1,
                            $token5
                        ),
                    ]
                ),
            ],
            [
                'NOT NOT one NOT NOT NOT two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new WordToken('one', 8, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 12),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 16),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 20),
                    $token7 = new WordToken('two', 24, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new LogicalNot(
                                new Term($token3),
                                $token2
                            ),
                            $token1
                        ),
                        new LogicalNot(
                            new LogicalNot(
                                new LogicalNot(
                                    new Term($token7),
                                    $token6
                                ),
                                $token5
                            ),
                            $token4
                        ),
                    ]
                ),
            ],
            [
                'NOT !one NOT !!two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new WordToken('one', 5, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 9),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 13),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 14),
                    $token7 = new WordToken('two', 15, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new LogicalNot(
                                new Term($token3),
                                $token2
                            ),
                            $token1
                        ),
                        new LogicalNot(
                            new LogicalNot(
                                new LogicalNot(
                                    new Term($token7),
                                    $token6
                                ),
                                $token5
                            ),
                            $token4
                        ),
                    ]
                ),
            ],
            [
                'one AND NOT "two"',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new PhraseToken('"two"', 12, '', '"', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token4),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one AND NOT @two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new UserToken('@two', 12, '@', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token4),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
            [
                'one AND NOT #two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new TagToken('#two', 12, '#', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token4),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
            ],
        ];
    }

    public function providerForTestQueryCorrected()
    {
        return [
            [
                'one"',
                'one',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_BAILOUT, '"', 3),
                ],
                new Query(
                    [
                        new Term($token1),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BAILOUT_TOKEN_IGNORED, $token2),
                ],
            ],
            [
                'one AND two AND',
                'one AND two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new WordToken('two', 8, '', 'two'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 12),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED, $token4),
                ],
            ],
            [
                'AND one AND two',
                'one AND two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    $token2 = new WordToken('one', 4, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 8),
                    $token4 = new WordToken('two', 12, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token2),
                            new Term($token4),
                            $token3
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                ],
            ],
            [
                'AND AND one AND AND two',
                'one two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new WordToken('one', 8, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 12),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 16),
                    $token6 = new WordToken('two', 20, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token3),
                        new Term($token6),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token4, $token5),
                ],
            ],
            [
                'OR one OR two',
                'one OR two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    $token2 = new WordToken('one', 3, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 7),
                    $token4 = new WordToken('two', 10, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token2),
                            new Term($token4),
                            $token3
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                ],
            ],
            [
                'OR OR one OR OR two',
                'one two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 3),
                    $token3 = new WordToken('one', 6, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 10),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 13),
                    $token6 = new WordToken('two', 16, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token3),
                        new Term($token6),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token4, $token5),
                ],
            ],
            [
                'OR OR one OR OR AND two',
                'one two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 3),
                    $token3 = new WordToken('one', 6, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 10),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 13),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 16),
                    $token7 = new WordToken('two', 20, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token3),
                        new Term($token7),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token4, $token5, $token6),
                ],
            ],
            [
                'one OR two AND OR NOT',
                'one OR two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new WordToken('two', 7, '', 'two'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 11),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 15),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 18),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token1),
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token4, $token5),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token6),
                ],
            ],
            [
                'AND OR one AND OR two AND OR three',
                'one two three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new WordToken('one', 7, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 11),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 15),
                    $token6 = new WordToken('two', 18, '', 'two'),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 22),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 26),
                    $token9 = new WordToken('three', 29, '', 'three'),
                ],
                new Query(
                    [
                        new Term($token3),
                        new Term($token6),
                        new Term($token9),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token4, $token5),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token7, $token8),
                ],
            ],
            [
                'OR AND one OR AND two OR AND three',
                'one two three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 3),
                    $token3 = new WordToken('one', 7, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 11),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 14),
                    $token6 = new WordToken('two', 18, '', 'two'),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 22),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 25),
                    $token9 = new WordToken('three', 29, '', 'three'),
                ],
                new Query(
                    [
                        new Term($token3),
                        new Term($token6),
                        new Term($token9),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token4, $token5),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token7, $token8),
                ],
            ],
            [
                'one AND NOT AND two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 12),
                    $token5 = new WordToken('two', 16, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token5),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token2, $token3, $token4),
                ],
            ],
            [
                'one NOT AND two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 8),
                    $token4 = new WordToken('two', 12, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token2, $token3),
                ],
            ],
            [
                'one NOT AND NOT two',
                'one NOT two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 8),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 12),
                    $token5 = new WordToken('two', 16, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token2, $token3),
                ],
            ],
            [
                'one OR NOT OR two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 7),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 11),
                    $token5 = new WordToken('two', 14, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token5),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token2, $token3, $token4),
                ],
            ],
            [
                'one NOT OR two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 8),
                    $token4 = new WordToken('two', 11, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token2, $token3),
                ],
            ],
            [
                'one NOT OR NOT two',
                'one NOT two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 8),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 11),
                    $token5 = new WordToken('two', 15, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token2, $token3),
                ],
            ],
            [
                '(one AND two OR NOT)',
                '(one AND two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new WordToken('two', 9, '', 'two'),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 13),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 16),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 19),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalAnd(
                                    new Term($token2),
                                    new Term($token4),
                                    $token3
                                ),
                            ],
                            $token1,
                            $token7
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED, $token5),
                ],
            ],
            [
                '(AND one OR two)',
                '(one OR two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 1),
                    $token3 = new WordToken('one', 5, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 9),
                    $token5 = new WordToken('two', 12, '', 'two'),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 15),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalOr(
                                    new Term($token3),
                                    new Term($token5),
                                    $token4
                                ),
                            ],
                            $token1,
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                'AND (((OR AND one AND NOT OR))) OR NOT',
                '(((one)))',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 0),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new GroupBeginToken('(', 6, '(', null),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 7),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 10),
                    $token7 = new WordToken('one', 14, '', 'one'),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 18),
                    $token9 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 22),
                    $token10 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 26),
                    $token11 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 28),
                    $token12 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 29),
                    $token13 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 30),
                    $token14 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 32),
                    $token15 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 35),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new Group(
                                    [
                                        new Group(
                                            [
                                                new Term($token7),
                                            ],
                                            $token4,
                                            $token11
                                        ),
                                    ],
                                    $token3,
                                    $token12
                                ),
                            ],
                            $token2,
                            $token13
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token5),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token8, $token9, $token10),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token15),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED, $token14),
                ],
            ],
            [
                'one ()',
                'one',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                ],
                new Query(
                    [
                        new Term($token1),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token3),
                ],
            ],
            [
                'one (())',
                'one',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 7),
                ],
                new Query(
                    [
                        new Term($token1),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token3, $token4),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token5),
                ],
            ],
            [
                'one AND (()) OR two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new GroupBeginToken('(', 8, '(', null),
                    $token4 = new GroupBeginToken('(', 9, '(', null),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 10),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 11),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 13),
                    $token8 = new WordToken('two', 16, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token8),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token4, $token5),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token3, $token6, $token7),
                ],
            ],
            [
                'one (AND OR NOT)',
                'one',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 9),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 12),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 15),
                ],
                new Query(
                    [
                        new Term($token1),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token3),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token4),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token5),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token6),
                ],
            ],
            [
                'one) (AND)) OR NOT)',
                'one',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 3),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 6),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 9),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 10),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 12),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 15),
                    $token9 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 18),
                ],
                new Query(
                    [
                        new Term($token1),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED, $token9),
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token4),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token3, $token5, $token7),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token8),
                ],
            ],
            [
                '(one( (AND) OR NOT((',
                'one',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new GroupBeginToken('(', 4, '(', null),
                    $token4 = new GroupBeginToken('(', 6, '(', null),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 7),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 10),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 12),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 15),
                    $token9 = new GroupBeginToken('(', 18, '(', null),
                    $token10 = new GroupBeginToken('(', 19, '(', null),
                ],
                new Query(
                    [
                        new Term($token2),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token10),
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token9),
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token3),
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token5),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token4, $token6, $token7),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token8),
                ],
            ],
            [
                'OR NOT (one OR two AND OR NOT) OR three AND NOT',
                'NOT (one OR two) OR three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 3),
                    $token3 = new GroupBeginToken('(', 7, '(', null),
                    $token4 = new WordToken('one', 8, '', 'one'),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 12),
                    $token6 = new WordToken('two', 15, '', 'two'),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 19),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 23),
                    $token9 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 26),
                    $token10 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 29),
                    $token11 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 31),
                    $token12 = new WordToken('three', 34, '', 'three'),
                    $token13 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 40),
                    $token14 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 44),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new LogicalNot(
                                new Group(
                                    [
                                        new LogicalOr(
                                            new Term($token4),
                                            new Term($token6),
                                            $token5
                                        ),
                                    ],
                                    $token3,
                                    $token10
                                ),
                                $token2
                            ),
                            new Term($token12),
                            $token11
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED, $token7, $token8),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token9),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token14),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED, $token13),
                ],
            ],
            [
                '+ one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 2, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token2),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token1),
                ],
            ],
            [
                '! one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 0),
                    $token2 = new WordToken('one', 2, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token2),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token1),
                ],
            ],
            [
                '+++one ++two',
                '+one +two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 1),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 2),
                    $token4 = new WordToken('one', 3, '', 'one'),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 7),
                    $token6 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token7 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token4),
                            $token3
                        ),
                        new Mandatory(
                            new Term($token7),
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token5),
                ],
            ],
            [
                '+one + +AND +++ two',
                '+one AND two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 7),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 8),
                    $token6 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 12),
                    $token7 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 13),
                    $token8 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 14),
                    $token9 = new WordToken('two', 16, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Mandatory(
                                new Term($token2),
                                $token1
                            ),
                            new Term($token9),
                            $token5
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token3),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token4),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token7),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token8),
                ],
            ],
            [
                '+one + +OR++ +two ++ +',
                '+one OR +two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 7),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 8),
                    $token6 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 10),
                    $token7 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 11),
                    $token8 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 13),
                    $token9 = new WordToken('two', 14, '', 'two'),
                    $token10 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 18),
                    $token11 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 19),
                    $token12 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 21),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Mandatory(
                                new Term($token2),
                                $token1
                            ),
                            new Mandatory(
                                new Term($token9),
                                $token8
                            ),
                            $token5
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token3),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token4),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token7),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token10),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token11),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token12),
                ],
            ],
            [
                'NOT +one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token1),
                ],
            ],
            [
                '+(+one + +OR++ +two ++ +)',
                '+(+one OR +two)',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 2),
                    $token4 = new WordToken('one', 3, '', 'one'),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 7),
                    $token6 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 9),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 10),
                    $token8 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 12),
                    $token9 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 13),
                    $token10 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 15),
                    $token11 = new WordToken('two', 16, '', 'two'),
                    $token12 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 20),
                    $token13 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 21),
                    $token14 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 23),
                    $token15 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 24),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Group(
                                [
                                    new LogicalOr(
                                        new Mandatory(
                                            new Term($token4),
                                            $token3
                                        ),
                                        new Mandatory(
                                            new Term($token11),
                                            $token10
                                        ),
                                        $token7
                                    ),
                                ],
                                $token2,
                                $token15
                            ),
                            $token1
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token5),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token8),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token9),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token12),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token13),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token14),
                ],
            ],
            [
                '- one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 2, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token2),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token1),
                ],
            ],
            [
                '---one --two',
                '-one -two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 1),
                    $token3 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 2),
                    $token4 = new WordToken('one', 3, '', 'one'),
                    $token5 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 7),
                    $token6 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 8),
                    $token7 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Term($token4),
                            $token3
                        ),
                        new Prohibited(
                            new Term($token7),
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token5),
                ],
            ],
            [
                '-one - -AND --- two',
                '-one AND two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 5),
                    $token4 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 7),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 8),
                    $token6 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 12),
                    $token7 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 13),
                    $token8 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 14),
                    $token9 = new WordToken('two', 16, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Prohibited(
                                new Term($token2),
                                $token1
                            ),
                            new Term($token9),
                            $token5
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token3),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token4),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token7),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token8),
                ],
            ],
            [
                '-one - -OR-- -two -- -',
                '-one OR -two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 5),
                    $token4 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 7),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 8),
                    $token6 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 10),
                    $token7 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 11),
                    $token8 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 13),
                    $token9 = new WordToken('two', 14, '', 'two'),
                    $token10 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 18),
                    $token11 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 19),
                    $token12 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 21),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Prohibited(
                                new Term($token2),
                                $token1
                            ),
                            new Prohibited(
                                new Term($token9),
                                $token8
                            ),
                            $token5
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token3),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token4),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token7),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token10),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token11),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token12),
                ],
            ],
            [
                'NOT -one',
                '-one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 4),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token1),
                ],
            ],
            [
                '-(-one - -OR-- -two --)-',
                '-(-one OR -two)',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 2),
                    $token4 = new WordToken('one', 3, '', 'one'),
                    $token5 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 7),
                    $token6 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 9),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 10),
                    $token8 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 12),
                    $token9 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 13),
                    $token10 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 15),
                    $token11 = new WordToken('two', 16, '', 'two'),
                    $token12 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 20),
                    $token13 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 21),
                    $token15 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 22),
                    $token14 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 23),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Group(
                                [
                                    new LogicalOr(
                                        new Prohibited(
                                            new Term($token4),
                                            $token3
                                        ),
                                        new Prohibited(
                                            new Term($token11),
                                            $token10
                                        ),
                                        $token7
                                    ),
                                ],
                                $token2,
                                $token15
                            ),
                            $token1
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token5),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token6),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token8),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token9),
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token12),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token13),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token14),
                ],
            ],
            [
                '+NOT one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 1),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                ],
            ],
            [
                '+AND one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 1),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token3),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                '+OR one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 1),
                    $token3 = new WordToken('one', 4, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token3),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                '-NOT one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 1),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                ],
            ],
            [
                '-AND one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 1),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token3),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                '-OR one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 1),
                    $token3 = new WordToken('one', 4, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token3),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token1),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                'NOT (one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token3),
                            $token1
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token2),
                ],
            ],
            [
                'NOT (one two',
                'NOT one two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new WordToken('one', 5, '', 'one'),
                    $token4 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token3),
                            $token1
                        ),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token2),
                ],
            ],
            [
                '-(one',
                '-one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Term($token3),
                            $token1
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token2),
                ],
            ],
            [
                '-(one two',
                '-one two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new WordToken('two', 6, '', 'two'),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Term($token3),
                            $token1
                        ),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token2),
                ],
            ],
            [
                '+(one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token3),
                            $token1
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token2),
                ],
            ],
            [
                '+(one two',
                '+one two',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new WordToken('two', 6, '', 'two'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token3),
                            $token1
                        ),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token2),
                ],
            ],
            [
                '-(one +(two NOT (three',
                '-one +two NOT three',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new WordToken('one', 2, '', 'one'),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 6),
                    $token5 = new GroupBeginToken('(', 7, '(', null),
                    $token6 = new WordToken('two', 8, '', 'two'),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 12),
                    $token8 = new GroupBeginToken('(', 16, '(', null),
                    $token9 = new WordToken('three', 17, '', 'three'),
                ],
                new Query(
                    [
                        new Prohibited(
                            new Term($token3),
                            $token1
                        ),
                        new Mandatory(
                            new Term($token6),
                            $token4
                        ),
                        new LogicalNot(
                            new Term($token9),
                            $token7
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token8),
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token5),
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token2),
                ],
            ],
            [
                'one AND NOT (two',
                'one AND NOT two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new GroupBeginToken('(', 12, '(', null),
                    $token5 = new WordToken('two', 13, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new LogicalNot(
                                new Term($token5),
                                $token3
                            ),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED, $token4),
                ],
            ],
            [
                '(one OR two AND) AND',
                '(one OR two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new WordToken('two', 8, '', 'two'),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 12),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 15),
                    $token7 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 17),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalOr(
                                    new Term($token2),
                                    new Term($token4),
                                    $token3
                                ),
                            ],
                            $token1,
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED, $token5),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED, $token7),
                ],
            ],
            [
                '(one AND NOT +two)',
                '(one AND +two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 9),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 13),
                    $token6 = new WordToken('two', 14, '', 'two'),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 17),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalAnd(
                                    new Term($token2),
                                    new Mandatory(
                                        new Term($token6),
                                        $token5
                                    ),
                                    $token3
                                ),
                            ],
                            $token1,
                            $token7
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token4),
                ],
            ],
            [
                '(one AND NOT -two)',
                '(one AND -two)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 9),
                    $token5 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 13),
                    $token6 = new WordToken('two', 14, '', 'two'),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 17),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalAnd(
                                    new Term($token2),
                                    new Prohibited(
                                        new Term($token6),
                                        $token5
                                    ),
                                    $token3
                                ),
                            ],
                            $token1,
                            $token7
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token4),
                ],
            ],
            [
                '(one AND NOT -two three)',
                '(one AND -two three)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 9),
                    $token5 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 13),
                    $token6 = new WordToken('two', 14, '', 'two'),
                    $token7 = new WordToken('three', 18, '', 'three'),
                    $token8 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 23),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalAnd(
                                    new Term($token2),
                                    new Prohibited(
                                        new Term($token6),
                                        $token5
                                    ),
                                    $token3
                                ),
                                new Term($token7),
                            ],
                            $token1,
                            $token8
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token4),
                ],
            ],
            [
                '(one AND NOT +two three)',
                '(one AND +two three)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new WordToken('one', 1, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 9),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 13),
                    $token6 = new WordToken('two', 14, '', 'two'),
                    $token7 = new WordToken('three', 18, '', 'three'),
                    $token8 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 23),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalAnd(
                                    new Term($token2),
                                    new Mandatory(
                                        new Term($token6),
                                        $token5
                                    ),
                                    $token3
                                ),
                                new Term($token7),
                            ],
                            $token1,
                            $token8
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token4),
                ],
            ],
            [
                '+()+one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 2),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 3),
                    $token5 = new WordToken('one', 4, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                ],
            ],
            [
                '+()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 2),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 3),
                    $token5 = new WordToken('one', 4, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                ],
            ],
            [
                'one AND +()!two',
                'one !two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token4 = new GroupBeginToken('(', 9, '(', null),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 10),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 11),
                    $token7 = new WordToken('two', 12, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token7),
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token3, $token4, $token5),
                ],
            ],
            [
                'NOT +()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 7),
                    $token6 = new WordToken('one', 8, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token6),
                            $token5
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3, $token4),
                ],
            ],
            [
                'NOT -()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 4),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 7),
                    $token6 = new WordToken('one', 8, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token6),
                            $token5
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3, $token4),
                ],
            ],
            [
                'NOT ++()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                    $token4 = new GroupBeginToken('(', 6, '(', null),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 7),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 8),
                    $token7 = new WordToken('one', 9, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token7),
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token3, $token4, $token5),
                ],
            ],
            [
                'NOT -+()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 4),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                    $token4 = new GroupBeginToken('(', 6, '(', null),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 7),
                    $token6 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 8),
                    $token7 = new WordToken('one', 9, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token7),
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token3, $token4, $token5),
                ],
            ],
            [
                'NOT !()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 7),
                    $token6 = new WordToken('one', 8, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token6),
                            $token5
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3, $token4),
                ],
            ],
            [
                'NOT +()+()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 7),
                    $token6 = new GroupBeginToken('(', 8, '(', null),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 9),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 10),
                    $token9 = new WordToken('one', 11, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token9),
                            $token8
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3, $token4),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token5, $token6, $token7),
                ],
            ],
            [
                'NOT NOT +()+()!one',
                '!one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token4 = new GroupBeginToken('(', 9, '(', null),
                    $token5 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 10),
                    $token6 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 11),
                    $token7 = new GroupBeginToken('(', 12, '(', null),
                    $token8 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 13),
                    $token9 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 14),
                    $token10 = new WordToken('one', 15, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token10),
                            $token9
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3, $token4, $token5),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token6, $token7, $token8),
                ],
            ],
            [
                'one AND NOT +()+()!two',
                'one !two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 12),
                    $token5 = new GroupBeginToken('(', 13, '(', null),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 14),
                    $token7 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 15),
                    $token8 = new GroupBeginToken('(', 16, '(', null),
                    $token9 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 17),
                    $token10 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 18),
                    $token11 = new WordToken('two', 19, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token11),
                            $token10
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token3, $token4, $token5, $token6),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token7, $token8, $token9),
                ],
            ],
            [
                'one AND NOT NOT +()+()!two',
                'one !two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 12),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 16),
                    $token6 = new GroupBeginToken('(', 17, '(', null),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 18),
                    $token8 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 19),
                    $token9 = new GroupBeginToken('(', 20, '(', null),
                    $token10 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 21),
                    $token11 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 22),
                    $token12 = new WordToken('two', 23, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token12),
                            $token11
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token3, $token4, $token5, $token6, $token7),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token8, $token9, $token10),
                ],
            ],
            [
                'one -() +() two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 4),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token6 = new GroupBeginToken('(', 9, '(', null),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 10),
                    $token8 = new WordToken('two', 12, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token8),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token3, $token4),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token5, $token6, $token7),
                ],
            ],
            [
                'one !+ two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                    $token4 = new WordToken('two', 7, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token3),
                ],
            ],
            [
                'one +! two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 5),
                    $token4 = new WordToken('two', 7, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token3),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                'one !- two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 5),
                    $token4 = new WordToken('two', 7, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token3),
                ],
            ],
            [
                'one !AND two',
                'one AND two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 5),
                    $token4 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalAnd(
                            new Term($token1),
                            new Term($token4),
                            $token3
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                ],
            ],
            [
                'one !OR two',
                'one OR two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 5),
                    $token4 = new WordToken('two', 8, '', 'two'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token1),
                            new Term($token4),
                            $token3
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                ],
            ],
            [
                'one +! two',
                'one two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 5),
                    $token4 = new WordToken('two', 7, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new Term($token4),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token3),
                ],
            ],
            [
                'NOT+ one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 3),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token3),
                            $token1
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                'NOT- one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 3),
                    $token3 = new WordToken('one', 5, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token3),
                            $token1
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED, $token2),
                ],
            ],
            [
                'NOT+one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 3),
                    $token3 = new WordToken('one', 4, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token3),
                            $token2
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token1),
                ],
            ],
            [
                '+()NOT one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 2),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 3),
                    $token5 = new WordToken('one', 7, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                ],
            ],
            [
                '-()NOT one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_PROHIBITED, '-', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 2),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 3),
                    $token5 = new WordToken('one', 7, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                ],
            ],
            [
                '+()NOT+()one',
                'one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 0),
                    $token2 = new GroupBeginToken('(', 1, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 2),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 3),
                    $token5 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 6),
                    $token6 = new GroupBeginToken('(', 7, '(', null),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 8),
                    $token8 = new WordToken('one', 9, '', 'one'),
                ],
                new Query(
                    [
                        new Term($token8),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token4, $token5, $token6, $token7),
                ],
            ],
            [
                'NOT()+one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new GroupBeginToken('(', 3, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 4),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                    $token5 = new WordToken('one', 6, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                ],
            ],
            [
                'NOT () NOT one',
                'NOT one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 7),
                    $token5 = new WordToken('one', 11, '', 'one'),
                ],
                new Query(
                    [
                        new LogicalNot(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                ],
            ],
            [
                'NOT () +one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new GroupBeginToken('(', 4, '(', null),
                    $token3 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
                    $token4 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 7),
                    $token5 = new WordToken('one', 8, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token5),
                            $token4
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3),
                ],
            ],
            [
                'NOT +()NOT +one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 7),
                    $token6 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 11),
                    $token7 = new WordToken('one', 12, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token7),
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3, $token4),
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token5),
                ],
            ],
            [
                'NOT +() NOT +one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 4),
                    $token3 = new GroupBeginToken('(', 5, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 6),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 8),
                    $token6 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 12),
                    $token7 = new WordToken('one', 13, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token7),
                            $token6
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token1, $token2, $token3, $token4),
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token5),
                ],
            ],
            [
                '(+()NOT one)AND',
                '(NOT one)',
                [
                    $token1 = new GroupBeginToken('(', 0, '(', null),
                    $token2 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 1),
                    $token3 = new GroupBeginToken('(', 2, '(', null),
                    $token4 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 3),
                    $token5 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token6 = new WordToken('one', 8, '', 'one'),
                    $token7 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 11),
                    $token8 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 12),
                ],
                new Query(
                    [
                        new Group(
                            [
                                new LogicalNot(
                                    new Term($token6),
                                    $token5
                                ),
                            ],
                            $token1,
                            $token7
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token2, $token3, $token4),
                    new Correction(Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED, $token8),
                ],
            ],
            [
                'one !NOT two',
                'one NOT two',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 5),
                    $token4 = new WordToken('two', 9, '', 'two'),
                ],
                new Query(
                    [
                        new Term($token1),
                        new LogicalNot(
                            new Term($token4),
                            $token3
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                ],
            ],
            [
                'NOT NOT +one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 4),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 8),
                    $token4 = new WordToken('one', 9, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token4),
                            $token3
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token1, $token2),
                ],
            ],
            [
                'NOT !+one',
                '+one',
                [
                    $token1 = new Token(Tokenizer::TOKEN_LOGICAL_NOT, 'NOT', 0),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_NOT_2, '!', 4),
                    $token3 = new Token(Tokenizer::TOKEN_MANDATORY, '+', 5),
                    $token4 = new WordToken('one', 6, '', 'one'),
                ],
                new Query(
                    [
                        new Mandatory(
                            new Term($token4),
                            $token3
                        ),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED, $token2),
                    new Correction(Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED, $token1),
                ],
            ],
            [
                'one OR two AND () three',
                'one OR two three',
                [
                    $token1 = new WordToken('one', 0, '', 'one'),
                    $token2 = new Token(Tokenizer::TOKEN_LOGICAL_OR, 'OR', 4),
                    $token3 = new WordToken('two', 7, '', 'two'),
                    $token4 = new Token(Tokenizer::TOKEN_LOGICAL_AND, 'AND', 11),
                    $token5 = new GroupBeginToken('(', 15, '(', null),
                    $token6 = new Token(Tokenizer::TOKEN_GROUP_END, ')', 16),
                    $token7 = new WordToken('three', 18, '', 'three'),
                ],
                new Query(
                    [
                        new LogicalOr(
                            new Term($token1),
                            new Term($token3),
                            $token2
                        ),
                        new Term($token7),
                    ]
                ),
                [
                    new Correction(Parser::CORRECTION_EMPTY_GROUP_IGNORED, $token4, $token5, $token6),
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestQuery
     *
     * @param string $string
     * @param \QueryTranslator\Values\Token[] $expectedTokens
     * @param \QueryTranslator\Languages\Galach\Values\Node\Query $expectedTree
     */
    public function testQuery($string, $expectedTokens, $expectedTree)
    {
        $this->doTestQuery($string, $string, $expectedTokens, $expectedTree, []);
    }

    /**
     * @dataProvider providerForTestQueryCorrected
     *
     * @param string $string
     * @param string $correctedString
     * @param \QueryTranslator\Values\Token[] $expectedTokens
     * @param \QueryTranslator\Languages\Galach\Values\Node\Query $query
     * @param \QueryTranslator\Values\Correction[] $corrections
     */
    public function testQueryCorrected($string, $correctedString, $expectedTokens, $query, $corrections)
    {
        $this->doTestQuery($string, $correctedString, $expectedTokens, $query, $corrections);
    }

    /**
     * @param string $string
     * @param string $expectedCorrectedString
     * @param \QueryTranslator\Values\Token[] $expectedTokens
     * @param \QueryTranslator\Languages\Galach\Values\Node\Query $query
     * @param \QueryTranslator\Values\Correction[] $corrections
     */
    protected function doTestQuery($string, $expectedCorrectedString, $expectedTokens, $query, $corrections)
    {
        $tokenExtractor = new TokenExtractor\Full();
        $tokenizer = new Tokenizer($tokenExtractor);
        $parser = new Parser();
        $generator = $this->getNativeGenerator();

        $tokenSequence = $tokenizer->tokenize($string);
        $this->assertInstanceOf(TokenSequence::class, $tokenSequence);

        $syntaxTree = $parser->parse($tokenSequence);
        $this->assertInstanceOf(SyntaxTree::class, $syntaxTree);

        $correctedString = $generator->generate($syntaxTree);

        $tokensWithoutWhitespace = [];
        foreach ($tokenSequence->tokens as $token) {
            if (Tokenizer::TOKEN_WHITESPACE !== $token->type) {
                $tokensWithoutWhitespace[] = $token;
            }
        }

        $this->assertEquals($expectedCorrectedString, $correctedString);
        $this->assertEquals($expectedTokens, $tokensWithoutWhitespace);
        $this->assertEquals($query, $syntaxTree->rootNode);
        $this->assertEquals($corrections, $syntaxTree->corrections);
        $this->assertEquals($tokenSequence, $syntaxTree->tokenSequence);
    }

    /**
     * @return \QueryTranslator\Languages\Galach\Generators\Native
     */
    protected function getNativeGenerator()
    {
        $visitors = [];

        $visitors[] = new Generators\Native\Group();
        $visitors[] = new Generators\Native\BinaryOperator();
        $visitors[] = new Generators\Native\Phrase();
        $visitors[] = new Generators\Native\Query();
        $visitors[] = new Generators\Native\Tag();
        $visitors[] = new Generators\Native\UnaryOperator();
        $visitors[] = new Generators\Native\User();
        $visitors[] = new Generators\Native\Word();

        $aggregate = new Generators\Common\Aggregate($visitors);

        return new Generators\Native($aggregate);
    }
}
