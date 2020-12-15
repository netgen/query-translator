# Query Translator

[![Build Status](https://img.shields.io/github/workflow/status/netgen/query-translator/Tests?style=flat-square)](https://github.com/netgen/query-translator/actions?query=workflow%3ATests)
[![Code Coverage](https://img.shields.io/codecov/c/github/netgen/query-translator.svg?style=flat-square)](https://codecov.io/gh/netgen/query-translator)
[![Downloads](https://img.shields.io/packagist/dt/netgen/query-translator.svg?style=flat-square)](https://packagist.org/packages/netgen/query-translator)
[![Latest stable](https://img.shields.io/packagist/v/netgen/query-translator.svg?style=flat-square)](https://packagist.org/packages/netgen/query-translator)
[![License](https://img.shields.io/packagist/l/netgen/query-translator.svg?style=flat-square)](https://packagist.org/packages/netgen/query-translator)
[![PHP](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://secure.php.net/)

Query Translator takes a search string as user input and converts it into something a search backend
can understand. Technically, it's a search query
[translator](https://en.wikipedia.org/wiki/Translator_(computing)) with
[abstract syntax tree](https://en.wikipedia.org/wiki/Abstract_syntax_tree) representation. From the
produced syntax tree, translation target can be anything you need. Usually it's a search backend,
like Solr and Elasticsearch, or a database abstraction layer.

A set of interfaces for implementing a language processor is provided, with a single implemented
language named [Galach](lib/Languages/Galach). Galach implements a syntax that is based on what
seems to be the unofficial standard for search query as user input. Quick cheat sheet:

`word` `"phrase"` `(group)` `+mandatory` `-prohibited` `AND` `&&` `OR` `||` `NOT` `!` `#tag` `@user`
`domain:term`

### Error handling

User input means you have to expect errors and handle them gracefully. Because of that, the parser
is completely resistant to errors. Syntax tree will contain detailed information about corrections
applied to make sense of the user input. This can be useful to clean up the input or implement rich
input interface, with features like suggestions, syntax highlighting and error feedback.

### Customization

The implementation was made with customization in mind. You can change the special characters which
will be used as part of the syntax, pick out elements of the language you want to use, implement
your own term clauses, or change how the syntax tree is converted to the target output.

### Some use cases

- User-level query language on top of your search backend
- Common query language on top of different search backends
- Control over options of the query language that is already provided by the search backend
- Better error handling than provided by the search backend
- Analysis and manipulation of the query before sending to the backend
- Customized query language (while remaining within the base syntax)
- Implementing rich input interface (with suggestions, syntax highlighting, error feedback)

Note: This implementation is intended as a
[library](https://en.wikipedia.org/wiki/Library_(computing)), meaning it doesn't try to solve
specific use cases for query translation. Instead, it's meant to be a base that you can use in
implementing such a use case.

### How to use

First add the library to your project:

```
composer require netgen/query-translator:^1.0
```

After that, make use of the features provided out of the box. If those are not enough, use extension
points to customize various parts of the translator to fit your needs. See
[Galach documentation](lib/Languages/Galach) to find out more.

## Run the demo

Demo is available as a separate repository at [netgen/query-translator-demo](https://github.com/netgen/query-translator-demo).

Steps for running the demo:

1. Create the demo project using composer `composer create-project netgen/query-translator-demo`
2. Position into the demo project directory `cd query-translator-demo`
3. Start the web server with `src` as the document root `php -S localhost:8005 -t src`
4. Open [http://localhost:8005](http://localhost:8005) in your browser ![Query Translator demo](https://raw.githubusercontent.com/netgen/query-translator-demo/master/src/animation.gif)
