<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\ExtendedDisMax;

use QueryTranslator\Languages\Galach\Generators\Lucene\Common\WordBase;

/**
 * Word Node Visitor implementation.
 */
final class Word extends WordBase
{
    /**
     * {@inheritdoc}
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
     *
     * Note: additionally to what is defined above we also escape blank space.
     *
     * @param string $string
     *
     * @return string
     */
    protected function escapeWord($string)
    {
        return preg_replace(
            '/(\\+|-|&&|\\|\\||!|\\(|\\)|\\{|}|\\[|]|\\^|"|~|\\*|\\?|:|\\/|\\\\| )/',
            '\\\\$1',
            $string
        );
    }
}
