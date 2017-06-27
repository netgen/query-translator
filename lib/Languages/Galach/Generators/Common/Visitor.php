<?php

namespace QueryTranslator\Languages\Galach\Generators\Common;

use QueryTranslator\Values\Node;

/**
 * Common base class for AST visitor implementations.
 */
abstract class Visitor
{
    /**
     * Check if visitor accepts the given $node.
     *
     * @param \QueryTranslator\Values\Node $node
     *
     * @return bool
     */
    abstract public function accept(Node $node);

    /**
     * Visit the given $node.
     *
     * @param \QueryTranslator\Values\Node $node
     * @param \QueryTranslator\Languages\Galach\Generators\Common\Visitor $subVisitor
     * @param mixed $options
     *
     * @return string
     */
    abstract public function visit(Node $node, Visitor $subVisitor = null, $options = null);
}
