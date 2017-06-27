<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot as LogicalNotNode;
use QueryTranslator\Values\Node;

/**
 * LogicalNot operator Node Visitor implementation.
 */
final class LogicalNot extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof LogicalNotNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof LogicalNotNode) {
            throw new LogicException(
                'Implementation accepts instance of LogicalNot Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clause = $subVisitor->visit($node->operand, $subVisitor, $options);

        return "NOT {$clause}";
    }
}
