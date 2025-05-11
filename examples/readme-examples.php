<?php


declare( strict_types = 1 );


require __DIR__ . '/../vendor/autoload.php';


( function () : void {

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

} )();
