<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieMatch;
use JDWX\Trie\TrieNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieMatch::class )]
final class TrieMatchTest extends TestCase {


    public function testConstruct() : void {
        $tn = new TrieNode( null, null );
        $tm = new TrieMatch( $tn, 'rest', [ 'foo' => 'bar' ] );
        self::assertSame( $tn, $tm->tn );
        self::assertSame( 'rest', $tm->stRest );
        self::assertSame( [ 'foo' => 'bar' ], $tm->rMatches );
    }


    public function testPath() : void {
        $tn = new TrieNode( null, null );
        $tm = new TrieMatch( $tn, 'rest', [ 'Foo' => 'Bar', 'Baz' => 'Qux' ] );
        self::assertSame( 'BarQux', $tm->path() );
    }


}
