<?php

namespace QueryTranslator\Languages\Galach\Generators\ExtendedDisMax;

use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\User as UserToken;
use QueryTranslator\Values\Node;

/**
 * User Node Visitor implementation.
 */
final class User extends Visitor
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
        return $node instanceof Term && $node->token instanceof UserToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Visitor implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof UserToken) {
            throw new LogicException(
                'Visitor implementation accepts instance of User Token'
            );
        }

        $fieldPrefix = $this->fieldName === null ? '' : "{$this->fieldName}:";

        return "{$fieldPrefix}{$token->user}";
    }
}
