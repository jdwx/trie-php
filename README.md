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

