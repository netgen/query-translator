<?php

namespace QueryTranslator\Languages\Galach\Generators;

use QueryTranslator\Languages\Galach\Generators\Native\Visitor;
use QueryTranslator\Values\SyntaxTree;

/**
 * EDisMax generator generates query string in Solr Extended DisMax query parser format.
 *
 * @link https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser
 */
final class EDisMax
{
    /**
     * @var \QueryTranslator\Languages\Galach\Generators\Native\Visitor
     */
    private $visitor;

    public function __construct(Visitor $visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * Generate query string in Solr Extended DisMax format from the given $syntaxTree.
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
