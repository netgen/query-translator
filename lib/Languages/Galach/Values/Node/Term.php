<?php

namespace QueryTranslator\Languages\Galach\Values\Node;

use QueryTranslator\Values\Node;
use QueryTranslator\Values\Token;

final class Term extends Node
{
    /**
     * @var \QueryTranslator\Values\Token
     */
    public $token;

    /**
     * @param \QueryTranslator\Values\Token $token
     */
    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    public function getNodes()
    {
        return [];
    }
}
