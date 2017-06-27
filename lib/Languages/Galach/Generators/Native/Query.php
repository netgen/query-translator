<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
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

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof QueryNode) {
            throw new LogicException(
                'Implementation accepts instance of Query Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clauses = [];

        foreach ($node->nodes as $subNode) {
            $clauses[] = $subVisitor->visit($subNode, $subVisitor, $options);
        }

        return implode(' ', $clauses);
    }
}
