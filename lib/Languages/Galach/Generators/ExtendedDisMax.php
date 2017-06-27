<?php

namespace QueryTranslator\Languages\Galach\Generators;

use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Values\SyntaxTree;

/**
 * ExtendedDisMax generator generates query string in Solr Extended DisMax query parser format.
 *
 * @link https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser
 */
final class ExtendedDisMax
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
     * Generate query string in Solr Extended DisMax format from the given $syntaxTree.
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
