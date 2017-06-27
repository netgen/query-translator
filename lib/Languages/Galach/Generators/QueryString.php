<?php

namespace QueryTranslator\Languages\Galach\Generators;

use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Values\SyntaxTree;

/**
 * QueryString generator generates query string in Elasticsearch Query String Query format.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
 */
final class QueryString
{
    /**
     * @var \QueryTranslator\Languages\Galach\Generators\Common\Visitor
     */
    private $visitor;

    public function __construct(Visitor $visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * Generate query string in Elasticsearch Query String Query format from the given $syntaxTree.
     *
     * @param \QueryTranslator\Values\SyntaxTree $syntaxTree
     * @param mixed $options
     *
     * @return string
     */
    public function generate(SyntaxTree $syntaxTree, $options = null)
    {
        return $this->visitor->visit($syntaxTree->rootNode, null, $options);
    }
}
