<?php

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException('Install dependencies using composer to run the demo.');
}

require_once __DIR__ . '/../../vendor/autoload.php';

use QueryTranslator\Languages\Galach\Generators\Native;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\TokenExtractor\Full;
use QueryTranslator\Languages\Galach\Values\Node\Exclude;
use QueryTranslator\Languages\Galach\Values\Node\Group;
use QueryTranslator\Languages\Galach\Values\Node\IncludeNode;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr;
use QueryTranslator\Languages\Galach\Values\Node\Query;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Phrase;
use QueryTranslator\Languages\Galach\Values\Token\Tag;
use QueryTranslator\Languages\Galach\Values\Token\User;
use QueryTranslator\Languages\Galach\Values\Token\Word;
use QueryTranslator\Values\Node;
use QueryTranslator\Values\SyntaxTree;
use QueryTranslator\Values\Token;
use QueryTranslator\Values\TokenSequence;

$json = file_get_contents('php://input');
$data = json_decode($json);

$tokenExtractor = new Full();
$tokenizer = new Tokenizer($tokenExtractor);
$parser = new Parser();
$generator = new Native(
    new Native\Aggregate(
        [
            new Native\Group(),
            new Native\LogicalAnd(),
            new Native\LogicalNot(),
            new Native\LogicalOr(),
            new Native\IncludeNode(),
            new Native\Phrase(),
            new Native\Exclude(),
            new Native\Query(),
            new Native\Tag(),
            new Native\Word(),
            new Native\User(),
        ]
    )
);

$tokenSequence = $tokenizer->tokenize('');
$syntaxTree = $parser->parse($tokenSequence);
$generator->generate($syntaxTree);

$startTime = microtime(true);

$tokenSequence = $tokenizer->tokenize($data->query);
$syntaxTree = $parser->parse($tokenSequence);
$nativeTranslation = $generator->generate($syntaxTree);

$elapsedTime = microtime(true) - $startTime;

$data = [
    'executionTime' => sprintf('%.6f', $elapsedTime),
    'syntaxTree' => SyntaxTreeRenderer::render($syntaxTree),
    'tokenTable' => TokenRenderer::renderTable($tokenSequence),
    'corrections' => CorrectionRenderer::render($syntaxTree),
    'correctionCount' => ' (' . count($syntaxTree->corrections) . ')',
    'translations' => TranslationRenderer::render($nativeTranslation),
];

header('Content-Type: application/json');
echo json_encode($data);

class TranslationRenderer
{
    public static function render($nativeTranslation)
    {
        $string = '<p><strong>Native</strong></p>';
        $string .= '<p>This is translation of the input string back to the input format.</p>';
        $string .= '<p>In difference to the input string, if corrections were applied, generated string will be corrected as well. Each whitespace sequence will be replaced by a single white space and special characters will be explicitly escaped.</p>';
        $string .= "<div class='overflow'><pre class='translation'><span>{$nativeTranslation}</span></pre></div>";

        return "<ol><li>{$string}</li></ol>";
    }
}

class CorrectionRenderer
{
    /**
     * @param \QueryTranslator\Values\SyntaxTree $syntaxTree
     *
     * @return string
     */
    public static function render(SyntaxTree $syntaxTree)
    {
        if (count($syntaxTree->corrections) === 0) {
            return '<p>No corrections were applied.</p>';
        }

        $corrections = '';
        foreach ($syntaxTree->corrections as $correction) {
            $description = self::getDescription($correction->type);
            $string = TokenRenderer::renderQueryString($syntaxTree->tokenSequence, $correction->tokens);
            $corrections .= "<li><p>{$description}</p></li><div class='overflow'><pre class='correction'>{$string}</pre></div>";
        }

        return "<p>Following corrections were applied:</p><ol>{$corrections}</ol>";
    }

    static private $descriptions = [
        Parser::CORRECTION_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED => 'Parser ignored unary operator preceding another operator',
        Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED => 'Parser ignored unary operator missing operand',
        Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED => 'Parser ignored binary operator missing left side operand',
        Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED => 'Parser ignored binary operator missing right side operand',
        Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED => 'Parser ignored binary operator following another operator',
        Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_INCLUSIVITY_IGNORED => 'Parser ignored logical not operators preceding inclusion/exclusion',
        Parser::CORRECTION_EMPTY_GROUP_IGNORED => 'Parser ignored empty group and connecting operators',
        Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED => 'Parser ignored unmatched left side group delimiter',
        Parser::CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED => 'Parser ignored unmatched right side group delimiter',
        Parser::CORRECTION_BAILOUT_TOKEN_IGNORED => 'Parser ignored bailout token',
    ];

    /**
     * Return description for a given correction $type.
     *
     * @param mixed $type
     *
     * @return string
     */
    public static function getDescription($type)
    {
        if (!isset(self::$descriptions[$type])) {
            return 'Undefined';
        }

        return self::$descriptions[$type];
    }
}

class TokenRenderer
{
    public static function renderQueryString(TokenSequence $tokenSequence, array $markTokens = [])
    {
        $string = '';

        foreach ($tokenSequence->tokens as $token) {
            $lexeme = htmlentities($token->lexeme);
            $name = self::getTokenTypeName($token);

            foreach ($markTokens as $markToken) {
                if ($markToken === $token) {
                    $string .= "<span title='{$name}' class='mark'>{$lexeme}</span>";
                    continue 2;
                }
            }

            $string .= "<span title='{$name}'>{$lexeme}</span>";
        }

        return $string;
    }

    public static function renderTable(TokenSequence $tokenSequence)
    {
        $head = '<thead><tr><th class="number">#</th><th>Type</th><th>Offset</th><th>Length</th><th>Lexeme position</th></tr></thead>';
        $body = '';

        foreach ($tokenSequence->tokens as $index => $token) {
            $number = $index + 1;
            $matchedTokenString = self::renderMatchedTokenString($tokenSequence, $token);
            $tokenTypeName = self::getTokenTypeName($token);
            $tokenLength = mb_strlen($token->lexeme);
            $cells = [];
            $cells[] = "<td class='number'>{$number}</td>";
            $cells[] = "<td>{$tokenTypeName}</td>";
            $cells[] = "<td class='number'>{$token->position}</td>";
            $cells[] = "<td class='number'>{$tokenLength}</td>";
            $cells[] = "<td class='source'><pre>{$matchedTokenString}</pre></td>";
            $class = '';
            if ($token->type === Tokenizer::TOKEN_BAILOUT) {
                $class = 'unknown';
            }
            $body .= "<tr class='{$class}'>" . implode('', $cells) . '</tr>';
        }

        return "<div class='overflow'><table>{$head}<tbody>{$body}</tbody></table></div>";
    }

    private static function getTokenTypeName(Token $token)
    {
        switch ($token->type) {
            case 1:
                return 'Whitespace';
            case 2:
                return 'Logical and';
            case 4:
                return 'Logical or';
            case 8:
                return 'Logical not';
            case 16:
                return 'Logical not (short)';
            case 32:
                return 'Include';
            case 64:
                return 'Exclude';
            case 128:
                return 'Left group delimiter';
            case 256:
                return 'Right group delimiter';
            case 512:
                return self::getTermTokenTypeName($token);
            case 1024:
                return 'BAILOUT';
        }

        throw new RuntimeException('Did not recognize given token');
    }

    private static function getTermTokenTypeName(Token $token)
    {
        switch (true) {
            case $token instanceof Phrase:
                return 'Phrase';
            case $token instanceof Word:
                return 'Word';
            case $token instanceof User:
                return 'User';
            case $token instanceof Tag:
                return 'Tag';
        }

        throw new RuntimeException('Did not recognize given token');
    }

    private static function renderMatchedTokenString(TokenSequence $tokenSequence, Token $token)
    {
        $start = htmlentities(mb_substr($tokenSequence->source, 0, $token->position));
        $end = htmlentities(mb_substr($tokenSequence->source, $token->position + mb_strlen($token->lexeme)));
        $lexeme = htmlentities($token->lexeme);
        $string = '';

        if (!empty($start)) {
            $string .= "<span class='before'>{$start}</span>";
        }

        $string .= "<span class='mark'>{$lexeme}</span>";
        if (!empty($end)) {
            $string .= "<span class='after'>{$end}</span>";
        }

        return $string;
    }
}

class SyntaxTreeRenderer
{
    /**
     * Renders structured tree representation of the given syntax $tree.
     *
     * @param \QueryTranslator\Values\SyntaxTree $syntaxTree
     *
     * @return string
     */
    public static function render(SyntaxTree $syntaxTree)
    {
        $namedArray = self::convert($syntaxTree->rootNode);
        $iterator = new RecursiveArrayIterator($namedArray);
        $treeIterator = new RecursiveTreeIterator($iterator);
        $treeIterator->setPrefixPart(RecursiveTreeIterator::PREFIX_LEFT, '');
        $treeIterator->setPrefixPart(RecursiveTreeIterator::PREFIX_RIGHT, '');
        $treeIterator->setPrefixPart(RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, ' │ ');
        $treeIterator->setPrefixPart(RecursiveTreeIterator::PREFIX_MID_LAST, '   ');
        $treeIterator->setPrefixPart(RecursiveTreeIterator::PREFIX_END_HAS_NEXT, ' ├─');
        $treeIterator->setPrefixPart(RecursiveTreeIterator::PREFIX_END_LAST, ' └─');

        $string = (string)$namedArray;
        $treeIterator->rewind();
        while ($treeIterator->valid()) {
            $string .= $treeIterator->getPrefix() . $treeIterator->getEntry();
            $treeIterator->next();
        }

        return "<pre>{$string}</pre>";
    }

    private static function getNodeName(Node $node)
    {
        switch (true) {
            case $node instanceof Term && $node->token instanceof Phrase:
                return '<span class="term">PHRASE</span>';
            case $node instanceof Term && $node->token instanceof Tag:
                return '<span class="term">TAG</span>';
            case $node instanceof Term && $node->token instanceof User:
                return '<span class="term">USER</span>';
            case $node instanceof Term && $node->token instanceof Word:
                return '<span class="term">WORD</span>';
            case $node instanceof LogicalAnd:
                return '<span class="operator">AND</span>';
            case $node instanceof LogicalOr:
                return '<span class="operator">OR</span>';
            case $node instanceof LogicalNot:
                return '<span class="operator">NOT</span>';
            case $node instanceof IncludeNode:
                return '<span class="operator">INCLUDE</span>';
            case $node instanceof Exclude:
                return '<span class="operator">EXCLUDE</span>';
            case $node instanceof Group:
                return '<span class="group">GROUP</span>';
            case $node instanceof Query:
                return '<span class="query">QUERY</span>';
        }

        throw new RuntimeException('Did not recognize given node');
    }

    private static function convert(Node $node)
    {
        $subObjects = [];

        if ($node instanceof Term) {
            $subObjects = self::getTermSubObjects($node);
        } else {
            foreach ($node->getNodes() as $subNode) {
                $subObjects[] = self::convert($subNode);
            }
        }

        return new NamedArrayObject(self::getNodeName($node), $subObjects);
    }

    private static function getTermSubObjects(Term $term)
    {
        $token = $term->token;

        switch (true) {
            case $term->token instanceof Phrase:
                /** @var \QueryTranslator\Languages\Galach\Values\Token\Phrase $token */
                return [
                    new NamedArrayObject('<span>phrase: <span>' . htmlentities($token->phrase) . '</span></span>'),
                    new NamedArrayObject('<span>domain: ' . htmlentities($token->domain ?: '~') . '</span>'),
                ];
            case $term->token instanceof Tag:
                /** @var \QueryTranslator\Languages\Galach\Values\Token\Tag $token */
                return [
                    new NamedArrayObject('<span>tag: <span>' . htmlentities($token->tag) . '</span></span>'),
                    new NamedArrayObject('<span>marker: ' . htmlentities($token->marker) . '</span>'),
                ];
            case $term->token instanceof User:
                /** @var \QueryTranslator\Languages\Galach\Values\Token\User $token */
                return [
                    new NamedArrayObject('<span>user: <span>' . htmlentities($token->user) . '</span></span>'),
                    new NamedArrayObject('<span>marker: ' . htmlentities($token->marker) . '</span>'),
                ];
            case $term->token instanceof Word:
                /** @var \QueryTranslator\Languages\Galach\Values\Token\Word $token */
                return [
                    new NamedArrayObject('<span>word: <span>' . htmlentities($token->word) . '</span></span>'),
                    new NamedArrayObject('<span>domain: ' . htmlentities($token->domain ?: '~') . '</span>'),
                ];
        }

        throw new RuntimeException('Did not recognize given node');
    }
}

/**
 * ArrayObject with a name, used for syntax tree rendering.
 */
class NamedArrayObject extends ArrayObject
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     * @param \NamedArrayObject[] $subObjects
     */
    public function __construct($name, array $subObjects = [])
    {
        $this->name = $name;
        parent::__construct($subObjects);
    }

    public function __toString()
    {
        return $this->name . "\n";
    }
}
