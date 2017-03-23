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

    public function visit(Node $node, Visitor $visitor = null)
    {
        /** @var \QueryTranslator\Languages\Galach\Values\Node\Query $node */
        $clauses = [];

        foreach ($node->nodes as $subNode) {
            $clauses[] = $visitor->visit($subNode, $visitor);
        }

        return implode(' ', $clauses);
    }
}
