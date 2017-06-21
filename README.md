# Query Translator

[![Build Status](https://img.shields.io/travis/netgen/query-translator.svg?style=flat-square)](https://travis-ci.org/netgen/query-translator)
[![Code Coverage](https://img.shields.io/codecov/c/github/netgen/query-translator.svg?style=flat-square)](https://codecov.io/gh/netgen/query-translator)
[![Downloads](https://img.shields.io/packagist/dt/netgen/query-translator.svg?style=flat-square)](https://packagist.org/packages/netgen/query-translator)
[![Latest stable](https://img.shields.io/packagist/v/netgen/query-translator.svg?style=flat-square)](https://packagist.org/packages/netgen/query-translator)
[![License](https://img.shields.io/packagist/l/netgen/query-translator.svg?style=flat-square)](https://packagist.org/packages/netgen/query-translator)

Query Translator is a search query [translator](https://en.wikipedia.org/wiki/Translator_(computing))
with [abstract syntax tree](https://en.wikipedia.org/wiki/Abstract_syntax_tree) representation.
It takes a search query as a user input and converts it into something a specific search
backend can understand. AST representation means that output of the parser is a hierarchical tree
structure. It represents the syntax of the given query in an abstract way and it's easy to process
using [tree traversal](https://en.wikipedia.org/wiki/Tree_traversal).

Query language implementation is named [Galach](https://github.com/netgen/query-translator/tree/master/lib/Languages/Galach).

### Use cases

1. Common query language on top of multiple search backends
2. Better control over options of the query language provided by the search backend
3. Post-processing user's query input
4. Customization of the query language
5. ...

This implementation is a [library](https://en.wikipedia.org/wiki/Library_(computing)), meaning it
doesn't intend to solve a specific use case for query translation. Instead, it's meant as a base that
you can use in implementing such a use case.

## Run the demo

1. Clone the repository and position into it
2. Generate autoloader using composer `composer dump-autoload -o`
3. Start the web server with demo document root `php -S localhost:8005 -t demo`
4. Open [http://localhost:8005](http://localhost:8005) in your browser
