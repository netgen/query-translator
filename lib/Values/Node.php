<?php

namespace QueryTranslator\Values;

/**
 * Node is a basic building element of the syntax tree.
 *
 * @see \QueryTranslator\Values\SyntaxTree
 */
abstract class Node
{
    /**
     * Return an array of sub-nodes.
     *
     * @return \QueryTranslator\Values\Node[]
     */
    abstract public function getNodes();
}
