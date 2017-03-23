<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use QueryTranslator\Languages\Galach\Values\Node\Query as QueryNode;
use QueryTranslator\Values\Node;

/**
 * Query Node Visitor implementation.
 */
final class Query extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof QueryNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\Query $node */
        $clauses = [];

        foreach ($node->nodes as $subNode) {
            $clauses[] = $subVisitor->visit($subNode, $subVisitor);
        }

        return implode(' ', $clauses);
    }
}
