<?php

namespace QueryTranslator\Tests\Galach\Tokenizer;

use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Token\GroupBegin as GroupBeginToken;
use QueryTranslator\Languages\Galach\Values\Token\Phrase as PhraseToken;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Token;

/**
 * Test case for tokenizer using Text token extractor.
 *
 * This inherits from FullTokenizerTest and overrides fixtures that behave differently.
 */
class TextTokenizerTest extends FullTokenizerTest
{
    /**
     * @var array
     */
    protected static $fixtureOverride;

    public static function setUpBeforeClass()
    {
        self::$fixtureOverride = [
            '#tag' => [
                new WordToken('#tag', 0, '', '#tag'),
            ],
            '\#tag' => [
                new WordToken('\#tag', 0, '', '\#tag'),
            ],
            '#_tag-tag' => [
                new WordToken('#_tag-tag', 0, '', '#_tag-tag'),
            ],
            '#tag+' => [
                new WordToken('#tag+', 0, '', '#tag+'),
            ],
            '#tag-' => [
                new WordToken('#tag-', 0, '', '#tag-'),
            ],
            '#tag!' => [
                new WordToken('#tag!', 0, '', '#tag!'),
            ],
            "#tag\n" => [
                new WordToken('#tag', 0, '', '#tag'),
                new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 4),
            ],
            '#tag ' => [
                new WordToken('#tag', 0, '', '#tag'),
                new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 4),
            ],
            '#tag(' => [
                new WordToken('#tag', 0, '', '#tag'),
                new GroupBeginToken('(', 4, '(', null),
            ],
            '#tag)' => [
                new WordToken('#tag', 0, '', '#tag'),
                new Token(Tokenizer::TOKEN_GROUP_END, ')', 4),
            ],
            '@user' => [
                new WordToken('@user', 0, '', '@user'),
            ],
            '@user.user' => [
                new WordToken('@user.user', 0, '', '@user.user'),
            ],
            '\@user' => [
                new WordToken('\@user', 0, '', '\@user'),
            ],
            '@_user-user' => [
                new WordToken('@_user-user', 0, '', '@_user-user'),
            ],
            '@user+' => [
                new WordToken('@user+', 0, '', '@user+'),
            ],
            '@user-' => [
                new WordToken('@user-', 0, '', '@user-'),
            ],
            '@user!' => [
                new WordToken('@user!', 0, '', '@user!'),
            ],
            "@user\n" => [
                new WordToken('@user', 0, '', '@user'),
                new Token(Tokenizer::TOKEN_WHITESPACE, "\n", 5),
            ],
            '@user ' => [
                new WordToken('@user', 0, '', '@user'),
                new Token(Tokenizer::TOKEN_WHITESPACE, ' ', 5),
            ],
            '@user(' => [
                new WordToken('@user', 0, '', '@user'),
                new GroupBeginToken('(', 5, '(', null),
            ],
            '@user)' => [
                new WordToken('@user', 0, '', '@user'),
                new Token(Tokenizer::TOKEN_GROUP_END, ')', 5),
            ],
            'domain:domain:' => [
                new WordToken('domain:domain:', 0, '', 'domain:domain:'),
            ],
            'domain:domain:domain:domain' => [
                new WordToken('domain:domain:domain:domain', 0, '', 'domain:domain:domain:domain'),
            ],
            'domain\:' => [
                new WordToken('domain\:', 0, '', 'domain\:'),
            ],
            'domain\::' => [
                new WordToken('domain\::', 0, '', 'domain\::'),
            ],
            'domain:word' => [
                new WordToken('domain:word', 0, '', 'domain:word'),
            ],
            'domain\:word' => [
                new WordToken('domain\:word', 0, '', 'domain\:word'),
            ],
            'domain:"phrase"' => [
                new WordToken('domain:', 0, '', 'domain:'),
                new PhraseToken('"phrase"', 7, '', '"', 'phrase'),
            ],
            'domain\:"phrase"' => [
                new WordToken('domain\:', 0, '', 'domain\:'),
                new PhraseToken('"phrase"', 8, '', '"', 'phrase'),
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
        $expectedTokens = $this->getExpectedFixtureWithOverride($string, $expectedTokens);
        parent::testTokenize($string, $expectedTokens);
    }

    /**
     * @param string $string
     * @param array $expectedTokens
     *
     * @return \QueryTranslator\Values\Token[]
     */
    protected function getExpectedFixtureWithOverride($string, array $expectedTokens)
    {
        if (isset(self::$fixtureOverride[$string])) {
            return self::$fixtureOverride[$string];
        }

        return $expectedTokens;
    }

    /**
     * @return \QueryTranslator\Languages\Galach\TokenExtractor
     */
    protected function getTokenExtractor()
    {
        return new TokenExtractor\Text();
    }
}
