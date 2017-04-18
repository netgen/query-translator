<?php

namespace QueryTranslator\Tests\Galach\Generators;

use QueryTranslator\Languages\Galach\Generators\QueryString;

/**
 * Test case for QueryString generator.
 */
class QueryStringTest extends ExtendedDisMaxTest
{
    public function providerForTestTranslation()
    {
        return array_merge(
            parent::providerForTestTranslation(),
            [
                [
                    '\\=',
                    '\\\\\\=',
                ],
                [
                    '\\>',
                    '\\\\\\>',
                ],
                [
                    '\\<',
                    '\\\\\\<',
                ],
            ]
        );
    }
    /**
     * @return \QueryTranslator\Languages\Galach\Generators\QueryString
     */
    protected function getGenerator()
    {
        $visitors = [];

        $visitors[] = new QueryString\Exclude();
        $visitors[] = new QueryString\Group(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );
        $visitors[] = new QueryString\IncludeNode();
        $visitors[] = new QueryString\LogicalAnd();
        $visitors[] = new QueryString\LogicalNot();
        $visitors[] = new QueryString\LogicalOr();
        $visitors[] = new QueryString\Phrase(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );
        $visitors[] = new QueryString\Query();
        $visitors[] = new QueryString\Tag(self::FIELD_TAG);
        $visitors[] = new QueryString\User(self::FIELD_USER);
        $visitors[] = new QueryString\Word(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );

        $aggregate = new QueryString\Aggregate($visitors);

        return new QueryString($aggregate);
    }
}
