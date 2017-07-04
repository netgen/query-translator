<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\QueryString;

use QueryTranslator\Languages\Galach\Generators\Lucene\Common\WordBase;

/**
 * Word Node Visitor implementation.
 */
final class Word extends WordBase
{
    /**
     * {@inheritdoc}
     *
     * @link http://lucene.apache.org/core/6_5_0/queryparser/org/apache/lucene/queryparser/classic/package-summary.html#Escaping_Special_Characters
     *
     * Note: additionally to what is defined above we also escape blank space.
     */
    protected function escapeWord($string)
    {
        return preg_replace(
            '/(\\+|-|\\=|&&|\\|\\||\\>|\\<|!|\\(|\\)|\\{|}|\\[|]|\\^|"|~|\\*|\\?|:|\\/|\\\\| )/',
            '\\\\$1',
            $string
        );
    }
}
