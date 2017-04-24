<?php

namespace QueryTranslator\Languages\Galach\Generators;

use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Values\SyntaxTree;

/**
 * Native Galach generator generates query string in Galach format.
 */
final class Native
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
     * Generate query string in Galach format from the given $syntaxTree.
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
