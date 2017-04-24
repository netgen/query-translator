<?php

namespace QueryTranslator\Tests\Galach\Generators;

use QueryTranslator\Languages\Galach\Generators\QueryString;
use QueryTranslator\Languages\Galach\Generators;

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

        $visitors[] = new Generators\Lucene\Common\Prohibited();
        $visitors[] = new Generators\Lucene\Common\Group(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );
        $visitors[] = new Generators\Lucene\Common\Mandatory();
        $visitors[] = new Generators\Lucene\Common\LogicalAnd();
        $visitors[] = new Generators\Lucene\Common\LogicalNot();
        $visitors[] = new Generators\Lucene\Common\LogicalOr();
        $visitors[] = new Generators\Lucene\Common\Phrase(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );
        $visitors[] = new Generators\Lucene\Common\Query();
        $visitors[] = new Generators\Lucene\Common\Tag(self::FIELD_TAG);
        $visitors[] = new Generators\Lucene\Common\User(self::FIELD_USER);
        $visitors[] = new Generators\Lucene\QueryString\Word(
            [
                self::FIELD_TEXT_DOMAIN => self::FIELD_TEXT_DOMAIN_MAPPED,
            ],
            self::FIELD_TEXT_DEFAULT
        );

        $aggregate = new Generators\Common\Aggregate($visitors);

        return new QueryString($aggregate);
    }
}
