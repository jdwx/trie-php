# trie-php

An adaptive radix tree implementation for PHP. Optionally supports defining variables along the path.

## Installation

You can require it directly with Composer:

```bash
composer require jdwx/trie
```

Or download the source from GitHub: https://github.com/jdwx/trie-php.git

## Requirements

This module requires PHP 8.3 or later. It has no other runtime dependencies.

## Usage

This implementation is slower than PHP's built-in associative arrays for straight lookups. It provides significant advantages when variable detection and partial matches are required.

The Trie class provides the ArrayAccess interface, so it can be used as a drop-in replacement for arrays.

```php

    $trie = new JDWX\Trie\Trie();
    $trie[ 'Foo' ] = 'FOO';
    $trie[ 'Foo/Bar' ] = 'BAR';
    $trie[ 'Foo/Bar/Baz' ] = 'BAZ';
    $trie[ 'Foo/Bar/Qux' ] = 'QUX';

    echo $trie[ 'Foo/Bar' ], "\n"; # => 'BAR'
    echo $trie[ 'Foo/Bar/Baz' ], "\n"; # => 'BAZ'
    echo $trie[ 'Foo/Bar/Baz/Quux' ] ?? '[null]', "\n"; # => [null]

    $trie = new JDWX\Trie\Trie( true );
    $trie[ 'Foo/${Bar}/Baz' ] = 'BAZ';

    echo $trie[ 'Foo/Qux/Baz' ], "\n"; # => 'BAZ'
    echo $trie->var( '$Bar' ), "\n"; # => 'Qux'

```

## Stability

This module has been subsequently refactored and largely rewritten to provide substantially simpler usage and more functionality prior to its public release. Consequently, it is not yet considered stable, especially around variable handling. (The method previously used had certain limitations and was very error-prone.)

## History

This module was first adapted from a private codebase in May 2025.
