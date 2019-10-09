# Galach query language

To better understand parts of the language processor described below, run the demo:

1. Create the demo project using composer `composer create-project netgen/query-translator-demo`
2. Position into the demo project directory `cd query-translator-demo`
3. Start the web server with `src` as the document root `php -S localhost:8005 -t src`
4. Open [http://localhost:8005](http://localhost:8005) in your browser

The demo will present behavior of Query Translator in an interactive way.

### Syntax

Galach is based on a syntax that seems to be the unofficial standard for search query as user input.
It should feel familiar, as the same basic syntax is used by any popular text-based search engine
out there. It is also very similar to
[Lucene Query Parser syntax](https://lucene.apache.org/core/2_9_4/queryparsersyntax.html), used by
both Solr and Elasticsearch.

Read about it more detail in the [syntax documentation](SYNTAX.md), here we'll only show a quick
cheat sheet:

`word` `"phrase"` `(group)` `+mandatory` `-prohibited` `AND` `&&` `OR` `||` `NOT` `!` `#tag` `@user`
`domain:term`

And an example:

```
cheese AND (bacon OR eggs) +type:breakfast
```

### How it works

The implementation has some of the usual language processor phases, starting with the lexical
analysis in [Tokenizer](Tokenizer.php), followed by the syntax analysis in [Parser](Parser.php), and
ending with the target code generation in a [Generator](Generators). The output of the Parser is a
hierarchical tree structure. It represents the syntax of the query in an abstract way and is easy to
process using [tree traversal](https://en.wikipedia.org/wiki/Tree_traversal). From that syntax tree,
a target output is generated.

When broken into parts, we have a sequence like this:

1. User writes a query string
2. Query string is given to Tokenizer which produces an instance of
[TokenSequence](../../Values/TokenSequence.php)
3. TokenSequence instance is given to Parser which produces an instance of
[SyntaxTree](../../Values/SyntaxTree.php)
4. SyntaxTree instance is given to the Generator to produce a target output
5. Target output is passed to its consumer

Here's how that would look in code:

```php
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\TokenExtractor\Full as FullTokenExtractor;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\Generators;

// 1. User writes a query string

$queryString = $_GET['query_string'];

// This is the place where you would perform some sanity checks that are out of the scope
// of this library, for example, checking the length of the query string

// 2. Query string is given to Tokenizer which produces an instance of TokenSequence

// Note that Tokenizer needs a TokenExtractor, which is an extension point
// Here we use Full TokenExtractor which provides full Galach syntax

$tokenExtractor = new FullTokenExtractor();
$tokenizer = new Tokenizer($tokenExtractor);
$tokenSequence = $tokenizer->tokenize($queryString);

// 3. TokenSequence instance is given to Parser which produces an instance of SyntaxTree

$parser = new Parser();
$syntaxTree = $parser->parse($tokenSequence);

// If needed, here you can access corrections

foreach ($syntaxTree->corrections as $correction) {
    echo $correction->type;
}
 
// 4. Now we can build a generator, in this example an ExtendedDisMax generator to target
//    Solr's Extended DisMax Query Parser

// This part is a little bit more involving since we need to build all visitors for different
// Nodes in the syntax tree

$generator = new Generators\ExtendedDisMax(
    new Generators\Common\Aggregate([
        new Generators\Lucene\Common\BinaryOperator(),
        new Generators\Lucene\Common\Group(),
        new Generators\Lucene\Common\Phrase(),
        new Generators\Lucene\Common\Query(),
        new Generators\Lucene\Common\Tag(),
        new Generators\Lucene\Common\UnaryOperator(),
        new Generators\Lucene\Common\User(),
        new Generators\Lucene\ExtendedDisMax\Word(),
    ])
);

// Now we can use the generator to generate the target output

$targetString = $generator->generate($syntaxTree);

// Finally we can send the generated string to Solr

$result = $solrClient->search($targetString);
```

### Error handling

No input is considered invalid. Both Tokenizer and Parser are made to be resistant to errors and
will try to process anything you throw at them. When input does contain an error, a correction will
be applied. This will be repeated as necessary. The corrections are applied during parsing and are
made available in the SyntaxTree as an array of [Correction](../../Values/Correction.php) instances.
They will contain information about the type of the correction and the tokens affected by it.

One type of correction starts in the Tokenizer. When no [Token](../../Values/Token.php) can be
extracted at a current position in the input string, a single character will be read as a special
`Tokenizer::TOKEN_BAILOUT` type Token. All Tokens of that type will be ignored by the parser. The
only known case where this can happen is the occurrence of an unclosed phrase delimiter `"`.

Note that, while applying the corrections, the best efforts are made to preserve the intended
meaning of the query. The following is a list of corrections, with correction type constant and an
example of an incorrect input and a corrected result.

1. Adjacent unary operator preceding another operator is ignored

    `Parser::CORRECTION_ADJACENT_UNARY_OPERATOR_PRECEDING_OPERATOR_IGNORED`

    ```
    ++one +-two
    ```
    ```
    +one -two
    ```

2. Unary operator missing an operand is ignored

    `Parser::CORRECTION_UNARY_OPERATOR_MISSING_OPERAND_IGNORED`

    ```
    one NOT
    ```
    ```
    one
    ```

3. Binary operator missing left side operand is ignored

    `Parser::CORRECTION_BINARY_OPERATOR_MISSING_LEFT_OPERAND_IGNORED`

    ```
    AND two
    ```
    ```
    two
    ```

4. Binary operator missing right side operand is ignored

    `Parser::CORRECTION_BINARY_OPERATOR_MISSING_RIGHT_OPERAND_IGNORED`

    ```
    one AND
    ```
    ```
    one
    ```

5. Binary operator following another operator is ignored together with connecting operators

    `Parser::CORRECTION_BINARY_OPERATOR_FOLLOWING_OPERATOR_IGNORED`

    ```
    one AND OR AND two
    ```
    ```
    one two
    ```

6. Logical not operators preceding mandatory or prohibited operator are ignored

    `Parser::CORRECTION_LOGICAL_NOT_OPERATORS_PRECEDING_PREFERENCE_IGNORED`

    ```
    NOT +one NOT -two
    ```
    ```
    +one -two
    ```

7. Empty group is ignored together with connecting operators

    `Parser::CORRECTION_EMPTY_GROUP_IGNORED`

    ```
    one AND () OR two
    ```
    ```
    one two
    ```

8. Unmatched left side group delimiter is ignored

    `Parser::CORRECTION_UNMATCHED_GROUP_LEFT_DELIMITER_IGNORED`

    ```
    one ( AND two
    ```
    ```
    one AND two
    ```

9. Unmatched right side group delimiter is ignored

    `Parser::CORRECTION_UNMATCHED_GROUP_RIGHT_DELIMITER_IGNORED`

    ```
    one AND ) two
    ```
    ```
    one AND two
    ```

10. Any Token of `Tokenizer::TOKEN_BAILOUT` type is ignored

    `Parser::CORRECTION_BAILOUT_TOKEN_IGNORED`

    ```
    one " two
    ```
    ```
    one two
    ```

### Customization

You can modify the Galach language in a limited way:

- By changing special characters and sequences of characters used as part of the language syntax:
    - operators: `AND` `&&` `OR` `||` `NOT` `!` `+` `-`
    - grouping and phrase delimiters: `(` `)` `"`
    - user and tag markers: `@` `#`
    - domain prefix: `domain:`
- By choosing parts of the language that you want to use. You might want to use only a subset of the
  full syntax, maybe without the grouping feature, using only `+` and `-` operators, disabling
  domains, and so on.
- By implementing custom `Tokenizer::TOKEN_TERM` type token. Read more on that in the text below.

Customization happens during the lexical analysis. The Tokenizer is actually marked as `final` and
is not intended for extending. You will need to implement your own
[TokenExtractor](TokenExtractor.php), a dependency to the Tokenizer. TokenExtractor controls the
syntax through regular expressions used to recognize the [Token](../../Values/Token.php), which is a
sequence of characters forming the smallest syntactic unit of the language. The following is a list
of supported Token types, together with their `Tokenizer::TOKEN_*` constants and an example:

1. Term token – represents a category of term type tokens.

    Note that [Word](Values/Token/Word.php) and [Phrase](Values/Token/Phrase.php) term tokens can
    have domain prefix. This can't be used on [User](Values/Token/User.php) and
    [Tag](Values/Token/Tag.php) term tokens, because those define implicit domains of their own.

    `Tokenizer::TOKEN_TERM`

    ```
    word
    ```
    ```
    title:word
    ```
    ```
    "this is a phrase"
    ```
    ```
    body:"this is a phrase"
    ```
    ```
    @user
    ```
    ```
    #tag
    ```

2. Whitespace token - represents the whitespace in the input string.

    `Tokenizer::TOKEN_WHITESPACE`

    ```
    one two
       ^
    ```

3. Logical AND token - combines two adjoining elements with logical AND.

    `Tokenizer::TOKEN_LOGICAL_AND`

    ```
    one AND two
        ^^^
    ```

4. Logical OR token - combines two adjoining elements with logical OR.

    `Tokenizer::TOKEN_LOGICAL_OR`

    ```
    one OR two
        ^^
    ```

5. Logical NOT token - applies logical NOT to the next (right-side) element.

    `Tokenizer::TOKEN_LOGICAL_NOT`

    ```
    NOT one
    ^^^
    ```

6. Shorthand logical NOT token - applies logical NOT to the next (right-side) element.

    This is an alternative to the `Tokenizer::TOKEN_LOGICAL_NOT` above, with the difference that
    parser will expect it's placed next (left) to the element it applies to, without the whitespace
    in between.

    `Tokenizer::TOKEN_LOGICAL_NOT_2`

    ```
    !one
    ^
    ```

7. Mandatory operator - applies mandatory inclusion to the next (right side) element.

    `Tokenizer::TOKEN_MANDATORY`

    ```
    +one
    ^
    ```

8. Prohibited operator - applies mandatory exclusion to the next (right side) element.

    `Tokenizer::TOKEN_PROHIBITED`

    ```
    -one
    ^
    ```

9. Left side delimiter of a group.

    Note that the left side group delimiter can have domain prefix.

    `Tokenizer::TOKEN_GROUP_BEGIN`

    ```
    (one AND two)
    ^
    ```
    ```
    text:(one AND two)
    ^^^^^^
    ```

10. Right side delimiter of a group.

    `Tokenizer::TOKEN_GROUP_END`

    ```
    (one AND two)
                ^
    ```

11. Bailout token.

    `Tokenizer::TOKEN_BAILOUT`

    ```
    not exactly a phrase"
                        ^
    ```

By changing the regular expressions, you can change how tokens are recognized, including special
characters used as part of the language syntax. You can also omit regular expressions for some token
types. Through that, you can control which elements of the language you want to use. There are two
abstract methods to implement when extending the base [TokenExtractor](TokenExtractor.php):

- `getExpressionTypeMap(): array`

    Here you must return a map of regular expressions to corresponding Token types. Token type
    can be one of the predefined constants `Tokenizer::TOKEN_*`.

- `createTermToken($position, array $data): Token`

    Here you receive Token data extracted through regular expression matching and a position where
    the data was extracted at. From that, you must return the corresponding Token instance of the
    `Tokenizer::TOKEN_TERM` type.

    If needed, here you can return an instance of your own Token subtype. You can use regular
    expressions with named capturing groups to extract meaning from the input string and pass it to
    the constructor method.

Optionally you can override the `createGroupBeginToken()` method. This is useful if you want to
customize token of the `Tokenizer::TOKEN_GROUP_BEGIN` type:

- `createGroupBeginToken($position, array $data): Token`

    Here you receive Token data extracted through regular expression matching and a position where
    the data was extracted at. From that, you must return the corresponding Token instance of the
    `Tokenizer::TOKEN_GROUP_BEGIN` type.

    If needed, here you can return an instance of your own Token subtype. You can use regular
    expressions with named capturing groups to extract meaning from the input string and pass it to
    the constructor method.

Two TokenExtractor implementations are provided out of the box. You can use them as an example and a
starting point to implement your own. These are:

- [Full](TokenExtractor/Full.php) TokenExtractor, supports full syntax of the language
- [Text](TokenExtractor/Text.php) TokenExtractor, supports text related subset of the language

#### Parser

The Parser is the core of the library. It's marked as `final` and is not intended for extending.
Method `Parser::parse()` accepts TokenSequence, but it only cares about the type of the Token, so it
will be oblivious to any customizations you might do in the Tokenizer. That includes both
recognizing only a subset of the full syntax and the custom `Tokenizer::TOKEN_TERM` type tokens.
While it's possible to implement a custom Parser, at that point you should consider calling it a new
language rather than a customization of Galach.

### Generators

A generator is used to generate the target output from the SyntaxTree. Three different ones are
provided out of the box:

1. [Native](Generators/Native.php)

   `Native` generator produces query string in the Galach format. This is mostly useful as an
   example and for the cleanup of the user input. In case the corrections were applied to the input,
   the output will be corrected. Also, it will not contain any superfluous whitespace and special
   characters will be explicitly escaped.

2. [ExtendedDisMax](Generators/ExtendedDisMax.php)

   Output of `ExtendedDisMax` generator is intended for the `q` parameter of the
   [Solr Extended DisMax Query Parser](https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser).

3. [QueryString](Generators/QueryString.php)

   Output of `QueryString` generator is intended for the `query` parameter of the
   [Elasticsearch Query String Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html).

All generators use the same hierarchical [Visitor](Generators/Common/Visitor.php) pattern. Each
concrete [Node](../../Values/Node.php) instance has its own visitor, dispatched by checking on the
class it implements. This enables customization per Node visitor. Since Term Node can cover
different Term tokens (including your custom ones), Term visitors should be dispatched both by the
Node instance and the type of Token it aggregates. The visit method also propagates optional
`$options` parameter. If needed, it can be used to control the behavior of the generator from the
outside.

This approach should be useful for most custom implementations.

Note that the Generator interface is not provided. That is because the generator's output can't be
assumed, because it's specific to the intended target. The main job of the Query Translator is
producing the syntax tree from which it's easy to generate anything you might need. Following from
that - if the provided generators don't meet your needs, feel free to customize them or implement
your own.
