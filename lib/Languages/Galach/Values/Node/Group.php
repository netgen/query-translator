<?php

namespace QueryTranslator\Languages\Galach\Values\Node;

use QueryTranslator\Languages\Galach\Values\Token\GroupBegin;
use QueryTranslator\Values\Node;
use QueryTranslator\Values\Token;

/**
 * Group Node Visitor implementation.
 */
final class Group extends Node
{
    /**
     * @var \QueryTranslator\Values\Node[]
     */
    public $nodes;

    /**
     * @var \QueryTranslator\Languages\Galach\Values\Token\GroupBegin
     */
    public $tokenLeft;

    /**
     * @var \QueryTranslator\Values\Token
     */
    public $tokenRight;

    /**
     * @param \QueryTranslator\Values\Node[] $nodes
     * @param \QueryTranslator\Languages\Galach\Values\Token\GroupBegin $tokenLeft
     * @param \QueryTranslator\Values\Token $tokenRight
     */
    public function __construct(
        array $nodes = [],
        GroupBegin $tokenLeft = null,
        Token $tokenRight = null
    ) {
        $this->nodes = $nodes;
        $this->tokenLeft = $tokenLeft;
        $this->tokenRight = $tokenRight;
    }

    public function getNodes()
    {
        return $this->nodes;
    }
}
