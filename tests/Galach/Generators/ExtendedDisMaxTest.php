<?php

namespace QueryTranslator\Tests\Galach\Generators;

use PHPUnit\Framework\TestCase;
use QueryTranslator\Languages\Galach\Generators;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;

/**
 * Test case for ExtendedDisMax generator.
 */
class ExtendedDisMaxTest extends TestCase
{
    const FIELD_USER = 'user_s';
    const FIELD_TAG = 'tags_ms';
    const FIELD_TEXT_DEFAULT = 'default_text_t';
    const FIELD_TEXT_DOMAIN = 'domain';
    const FIELD_TEXT_DOMAIN_MAPPED = 'special_text_t';

    public function providerForTestTranslation()
    {
        return [
            [
                'one',
                'one',
            ],
            [
                "'one'",
                '"one"',
            ],
            [
                'one two',
                'one two',
            ],
            [
                '(one two)',
                '(one two)',
            ],
            [
                'unexpected:(one two)',
                'default_text_t:(one two)',
            ],
            [
                'domain:(one two)',
                'special_text_t:(one two)',
            ],
            [
                'one AND two',
                'one AND two',
            ],
            [
                'one && two',
                'one AND two',
            ],
            [
                'one OR two',
                'one OR two',
            ],
            [
                'one || two',
                'one OR two',
            ],
            [
                'NOT one',
                'NOT one',
            ],
            [
                '!one',
                'NOT one',
            ],
            [
                '+one',
                '+one',
            ],
            [
                '-one',
                '-one',
            ],
            [
                '@user',
                'user_s:user',
            ],
            [
                '#tag',
                'tags_ms:tag',
            ],
            [
                'unexpected:one',
                'default_text_t:one',
            ],
            [
                'domain:one',
                'special_text_t:one',
            ],
            [
                "unexpected:'one'",
                'default_text_t:"one"',
            ],
            [
                "domain:'one'",
                'special_text_t:"one"',
            ],
            [
                '\\',
                '\\\\',
            ],
            [
                '\\+',
                '\\+',
            ],
            [
                '\\-',
                '\\-',
            ],
            [
                '\\&&',
                '\\\\\\&&',
            ],
            [
                '\\||',
                '\\\\\\||',
            ],
            [
                '\\!',
                '\\!',
            ],
            [
                '\\(',
                '\\(',
            ],
            [
                '\\)',
                '\\)',
            ],
            [
                '\\{',
                '\\\\\\{',
            ],
            [
                '\\}',
                '\\\\\\}',
            ],
            [
                '\\[',
                '\\\\\\[',
            ],
            [
                '\\]',
                '\\\\\\]',
            ],
            [
                '\\^',
                '\\\\\\^',
            ],
            [
                '\\"',
                '\\"',
            ],
            [
                '\\~',
                '\\\\\\~',
            ],
            [
                '\\*',
                '\\\\\\*',
            ],
            [
                '\\?',
                '\\\\\\?',
            ],
            [
                '\\:',
                '\\:',
            ],
            [
                '\\/',
                '\\\\\\/',
            ],
            [
                '\\\\',
                '\\\\',
            ],
            [
                '\\ ',
                '\\ ',
            ],
        ];
    }

    /**
     * @dataProvider providerForTestTranslation
     *
     * @param string $string
     * @param string $expectedTranslatedString
     */
    public function testTranslation($string, $expectedTranslatedString)
    {
        $tokenExtractor = new TokenExtractor\Full();
        $tokenizer = new Tokenizer($tokenExtractor);
        $parser = new Parser();
        $generator = $this->getGenerator();

        $tokenSequence = $tokenizer->tokenize($string);
        $syntaxTree = $parser->parse($tokenSequence);
        $translatedString = $generator->generate($syntaxTree);

        $this->assertEquals($expectedTranslatedString, $translatedString);
    }

    /**
     * @return \QueryTranslator\Languages\Galach\Generators\ExtendedDisMax
     */
    protected function getGenerator()
    {
        $visitors = [];

        $visitors[] = new Generators\ExtendedDisMax\Exclude();
        $visitors[] = new Generators\ExtendedDisMax\Group(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );
        $visitors[] = new Generators\ExtendedDisMax\IncludeNode();
        $visitors[] = new Generators\ExtendedDisMax\LogicalAnd();
        $visitors[] = new Generators\ExtendedDisMax\LogicalNot();
        $visitors[] = new Generators\ExtendedDisMax\LogicalOr();
        $visitors[] = new Generators\ExtendedDisMax\Phrase(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );
        $visitors[] = new Generators\ExtendedDisMax\Query();
        $visitors[] = new Generators\ExtendedDisMax\Tag(self::FIELD_TAG);
        $visitors[] = new Generators\ExtendedDisMax\User(self::FIELD_USER);
        $visitors[] = new Generators\ExtendedDisMax\Word(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );

        $aggregate = new Generators\ExtendedDisMax\Aggregate($visitors);

        return new Generators\ExtendedDisMax($aggregate);
    }
}
