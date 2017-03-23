<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use QueryTranslator\Values\Node;

/**
 * Base class for AST visitor implementations.
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
     * @param \QueryTranslator\Languages\Galach\Generators\Native\Visitor $subVisitor
     *
     * @return mixed
     */
    abstract public function visit(Node $node, Visitor $subVisitor = null);
}
