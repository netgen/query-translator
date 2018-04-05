<?php

namespace QueryTranslator\Languages\Galach\Generators\Native;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Range as RangeToken;
use QueryTranslator\Values\Node;

/**
 * Range Node Visitor implementation.
 */
final class Range extends Visitor
{
    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof RangeToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof RangeToken) {
            throw new LogicException(
                'Implementation accepts instance of Range Token'
            );
        }

        $domainPrefix = '' === $token->domain ? '' : "{$token->domain}:";

        switch ($token->type) {
            case RangeToken::TYPE_INCLUSIVE:
                return $domainPrefix . '[' . $token->rangeFrom . ' TO ' . $token->rangeTo . ']';

            case RangeToken::TYPE_EXCLUSIVE:
                return $domainPrefix . '{' . $token->rangeFrom . ' TO ' . $token->rangeTo . '}';

            default:
                throw new LogicException(sprintf('Range type %s is not supported', $token->type));
        }
    }
}
