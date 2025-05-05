<?php


declare( strict_types = 1 );


require __DIR__ . '/../vendor/autoload.php';


(function() : void {

    $trie = new JDWX\Trie\Trie();
    $trie->add( 'foo', 'FOO' );
    $trie->add( 'foo/bar', 'BAR' );
    $trie->add( 'foo/bar/baz', 'BAZ' );
    $trie->add( 'foo/bar/qux', 'QUX' );

    $trie->get( 'foo/bar' ); # => 'BAR'
    $trie->get( 'foo/bar/baz' ); # => 'BAZ'
    $trie->get( 'foo/bar/baz/quux' ); # => null

    $stPath = 'foo/bar/baz/quux';
    echo $trie->getPrefix( $stPath ), "\n"; # => BAZ
    echo "path = ", $stPath, "\n"; # => '/quux'



})();