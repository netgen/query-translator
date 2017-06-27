<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
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

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof GroupNode) {
            throw new LogicException(
                'Implementation accepts instance of Group Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clauses = [];

        foreach ($node->nodes as $subNode) {
            $clauses[] = $subVisitor->visit($subNode, $subVisitor, $options);
        }

        $clauses = implode(' ', $clauses);
        $domainPrefix = $node->tokenLeft->domain === '' ? '' : "{$node->tokenLeft->domain}:";

        return "{$domainPrefix}{$node->tokenLeft->delimiter}{$clauses}{$node->tokenRight->lexeme}";
    }
}
