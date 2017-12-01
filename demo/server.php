<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException('Install dependencies using composer to run the demo.');
}

require_once $autoload;

use QueryTranslator\Languages\Galach\Generators;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\TokenExtractor\Full;
use QueryTranslator\Languages\Galach\TokenExtractor\Text;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Node\Group;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited;
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

if (isset($_GET['full'])) {
    $tokenExtractor = new Full();
} else {
    $tokenExtractor = new Text();
}

$tokenizer = new Tokenizer($tokenExtractor);
$parser = new Parser();
$nativeGenerator = new Generators\Native(
    new Generators\Common\Aggregate(
        [
            new Generators\Native\Group(),
            new Generators\Native\BinaryOperator(),
            new Generators\Native\UnaryOperator(),
            new Generators\Native\Phrase(),
            new Generators\Native\Query(),
            new Generators\Native\Tag(),
            new Generators\Native\Word(),
            new Generators\Native\User(),
        ]
    )
);

$tokenSequence = $tokenizer->tokenize('');
$syntaxTree = $parser->parse($tokenSequence);
$nativeGenerator->generate($syntaxTree);

$startTime = microtime(true);

$tokenSequence = $tokenizer->tokenize($data->query);
$syntaxTree = $parser->parse($tokenSequence);
$nativeTranslation = $nativeGenerator->generate($syntaxTree);

$elapsedTime = microtime(true) - $startTime;

$data = [
    'executionTime' => sprintf('%.6f', $elapsedTime),
    'syntaxTree' => SyntaxTreeRenderer::render($syntaxTree),
    'tokenTable' => TokenRenderer::renderTable($tokenSequence),
    'corrections' => CorrectionRenderer::render($syntaxTree),
    'correctionCount' => ' (' . count($syntaxTree->corrections) . ')',
    'translations' => TranslationRenderer::render($syntaxTree, $nativeTranslation),
];

header('Content-Type: application/json');
echo json_encode($data);

class TranslationRenderer
{
    public static function render(SyntaxTree $syntaxTree, $nativeTranslation)
    {
        $nativeMarkup = '<p><strong>Native</strong></p>';
        $nativeMarkup .= '<p>This is translation of the input string back to the input format.</p>';
        $nativeMarkup .= '<p>In difference to the input string, if corrections were applied, generated string will be corrected as well. Each whitespace sequence will be replaced by a single blank space and special characters will be explicitly escaped.</p>';
        $nativeMarkup .= "<div class='overflow'><pre class='translation'><span>{$nativeTranslation}</span></pre></div>";
        $nativeMarkup = "<li>{$nativeMarkup}</li>";

        $extendedDisMaxTranslation = self::getExtendedDisMaxTranslation($syntaxTree);
        $extendedDisMaxMarkup = '<p><strong>ExtendedDisMax</strong></p>';
        $extendedDisMaxMarkup .= '<p>Translation for the <code>q</code> parameter of the <a href="https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser">Solr Extended DisMax Query Parser</a>.</p>';
        $extendedDisMaxMarkup .= "<div class='overflow'><pre class='translation'><span>{$extendedDisMaxTranslation}</span></pre></div>";
        $extendedDisMaxMarkup = "<li>{$extendedDisMaxMarkup}</li>";

        $queryStringTranslation = self::getQueryStringTranslation($syntaxTree);
        $queryStringMarkup = '<p><strong>QueryString</strong></p>';
        $queryStringMarkup .= '<p>Translation for the <code>query</code> parameter of the <a href="https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html">Elasticsearch Query String Query</a>.</p>';
        $queryStringMarkup .= "<div class='overflow'><pre class='translation'><span>{$queryStringTranslation}</span></pre></div>";
        $queryStringMarkup = "<li>{$queryStringMarkup}</li>";

        return "<ol>{$nativeMarkup}{$extendedDisMaxMarkup}{$queryStringMarkup}</ol>";
    }

    private static function getExtendedDisMaxTranslation(SyntaxTree $syntaxTree)
    {
        $visitors = [];

        $visitors[] = new Generators\Lucene\Common\Prohibited();
        $visitors[] = new Generators\Lucene\Common\Group(
            [
                'type' => 'demo_type_s',
            ],
            'demo_default_s'
        );
        $visitors[] = new Generators\Lucene\Common\Mandatory();
        $visitors[] = new Generators\Lucene\Common\LogicalAnd();
        $visitors[] = new Generators\Lucene\Common\LogicalNot();
        $visitors[] = new Generators\Lucene\Common\LogicalOr();
        $visitors[] = new Generators\Lucene\Common\Phrase(
            [
                'type' => 'demo_type_s',
            ],
            'demo_default_s'
        );
        $visitors[] = new Generators\Lucene\Common\Query();
        $visitors[] = new Generators\Lucene\Common\Tag('tag_ms');
        $visitors[] = new Generators\Lucene\Common\User('user_s');
        $visitors[] = new Generators\Lucene\ExtendedDisMax\Word(
            [
                'type' => 'demo_type_s',
            ],
            'demo_default_s'
        );

        $aggregate = new Generators\Common\Aggregate($visitors);
        $generator = new Generators\ExtendedDisMax($aggregate);

        return $generator->generate($syntaxTree);
    }

    private static function getQueryStringTranslation(SyntaxTree $syntaxTree)
    {
        $visitors = [];

        $visitors[] = new Generators\Lucene\Common\Prohibited();
        $visitors[] = new Generators\Lucene\Common\Group(
            [
                'type' => 'demo_type_s',
            ],
            'demo_default_s'
        );
        $visitors[] = new Generators\Lucene\Common\Mandatory();
        $visitors[] = new Generators\Lucene\Common\LogicalAnd();
        $visitors[] = new Generators\Lucene\Common\LogicalNot();
        $visitors[] = new Generators\Lucene\Common\LogicalOr();
        $visitors[] = new Generators\Lucene\Common\Phrase(
            [
                'type' => 'demo_type_s',
            ],
            'demo_default_s'
        );
        $visitors[] = new Generators\Lucene\Common\Query();
        $visitors[] = new Generators\Lucene\Common\Tag('tag_ms');
        $visitors[] = new Generators\Lucene\Common\User('user_s');
        $visitors[] = new Generators\Lucene\QueryString\Word(
            [
                'type' => 'demo_type_s',
            ],
            'demo_default_s'
        );

        $aggregate = new Generators\Common\Aggregate($visitors);
        $generator = new Generators\QueryString($aggregate);

        return $generator->generate($syntaxTree);
    }
}

class CorrectionRenderer
{
    private static $descriptions = [
        Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED => 'Parser ignored adjacent unary operator preceding another operator',
        Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED => 'Parser ignored unary operator missing an operand',
        Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED => 'Parser ignored binary operator missing left side operand',
        Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED => 'Parser ignored binary operator missing right side operand',
        Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED => 'Parser ignored binary operator following another operator and connecting operators',
        Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED => 'Parser ignored logical not operators preceding inclusion/exclusion',
        Parser::CORRECTION_EMPTY_GROUP_IGNORED => 'Parser ignored empty group and connecting operators',
        Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED => 'Parser ignored unmatched left side group delimiter',
        Parser::CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED => 'Parser ignored unmatched right side group delimiter',
        Parser::CORRECTION_BAILOUT_TOKEN_IGNORED => 'Parser ignored bailout token',
    ];

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
                return 'Mandatory';
            case 64:
                return 'Prohibited';
            case 128:
                return 'Group begin';
            case 256:
                return 'Group end';
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
     * Renders structured tree representation of the given $syntaxTree.
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
            case $node instanceof Mandatory:
                return '<span class="operator">MANDATORY</span>';
            case $node instanceof Prohibited:
                return '<span class="operator">PROHIBITED</span>';
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

        if ($node instanceof Group) {
            $subObjects = [
                new NamedArrayObject('<span>domain: ' . htmlentities($node->tokenLeft->domain ?: '~') . '</span>'),
                new NamedArrayObject('<span>clauses</span>', $subObjects),
            ];
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
                    new NamedArrayObject('<span>domain: ' . htmlentities($token->domain ?: '~') . '</span>'),
                    new NamedArrayObject('<span>phrase: <span>' . htmlentities($token->phrase) . '</span></span>'),
                ];
            case $term->token instanceof Tag:
                /** @var \QueryTranslator\Languages\Galach\Values\Token\Tag $token */
                return [
                    new NamedArrayObject('<span>marker: ' . htmlentities($token->marker) . '</span>'),
                    new NamedArrayObject('<span>tag: <span>' . htmlentities($token->tag) . '</span></span>'),
                ];
            case $term->token instanceof User:
                /** @var \QueryTranslator\Languages\Galach\Values\Token\User $token */
                return [
                    new NamedArrayObject('<span>marker: ' . htmlentities($token->marker) . '</span>'),
                    new NamedArrayObject('<span>user: <span>' . htmlentities($token->user) . '</span></span>'),
                ];
            case $term->token instanceof Word:
                /** @var \QueryTranslator\Languages\Galach\Values\Token\Word $token */
                return [
                    new NamedArrayObject('<span>domain: ' . htmlentities($token->domain ?: '~') . '</span>'),
                    new NamedArrayObject('<span>word: <span>' . htmlentities($token->word) . '</span></span>'),
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
