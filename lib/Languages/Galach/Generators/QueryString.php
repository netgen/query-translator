<?php

namespace QueryTranslator\Languages\Galach\Generators;

use QueryTranslator\Languages\Galach\Generators\QueryString\Visitor;
use QueryTranslator\Values\SyntaxTree;

/**
 * QueryString generator generates query string in Elasticsearch Query String Query format.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
 */
final class QueryString
{
    /**
     * @var \QueryTranslator\Languages\Galach\Generators\QueryString\Visitor
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
     *
     * @return string
     */
    public function generate(SyntaxTree $syntaxTree)
    {
        return $this->visitor->visit($syntaxTree->rootNode);
    }
}
