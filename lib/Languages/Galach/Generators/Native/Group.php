<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\Group as GroupNode;
use QueryTranslator\Values\Node;

/**
 * Group Node Visitor implementation.
 */
final class Group extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof GroupNode;
    }

    public function visit(Node $group, Visitor $subVisitor = null)
    {
        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        /** @var \QueryTranslator\Languages\Galach\Values\Node\Group $group */
        $clauses = [];

        foreach ($group->nodes as $node) {
            $clauses[] = $subVisitor->visit($node, $subVisitor);
        }

        $clauses = implode(' ', $clauses);

        return "{$group->tokenLeft->lexeme}{$clauses}{$group->tokenRight->lexeme}";
    }
}
