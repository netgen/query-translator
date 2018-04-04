<?php

namespace QueryTranslator\Languages\Galach\Generators\Lucene\Common;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Tag as TagToken;
use QueryTranslator\Values\Node;

/**
 * User Node Visitor implementation.
 */
final class Tag extends Visitor
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @param string $fieldName
     */
    public function __construct($fieldName = null)
    {
        $this->fieldName = $fieldName;
    }

    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof TagToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof TagToken) {
            throw new LogicException(
                'Implementation accepts instance of Tag Token'
            );
        }

        $fieldPrefix = null === $this->fieldName ? '' : "{$this->fieldName}:";

        return "{$fieldPrefix}{$token->tag}";
    }
}
