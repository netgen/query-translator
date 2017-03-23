<?php

namespace QueryTranslator\Languages\Galach\Values\Node;

use QueryTranslator\Values\Node;

final class Query extends Node
{
    /**
     * @var \QueryTranslator\Values\Node[]
     */
    public $nodes;

    /**
     * @param \QueryTranslator\Values\Node[] $nodes
     */
    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    public function getNodes()
    {
        return $this->nodes;
    }
}
