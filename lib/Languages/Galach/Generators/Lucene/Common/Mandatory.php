<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory as MandatoryNode;
use QueryTranslator\Values\Node;

/**
 * Mandatory operator Node Visitor implementation.
 */
final class Mandatory extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof MandatoryNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof MandatoryNode) {
            throw new LogicException(
                'Implementation accepts instance of Mandatory Node'
            );
        }

        if ($subVisitor === null) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clause = $subVisitor->visit($node->operand, $subVisitor, $options);

        return "+{$clause}";
    }
}
