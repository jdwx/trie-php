<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieMatch;
use JDWX\Trie\TrieNode;
use JDWX\Trie\TriePair;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieMatch::class )]
final class TrieMatchTest extends TestCase {


    public function testConstruct() : void {
        $tn = new TrieNode( null, null );
        $tm = new TrieMatch( $tn, 'rest', [ new TriePair( 'Foo', 'Bar' ) ] );
        self::assertSame( $tn, $tm->tn );
        self::assertSame( 'rest', $tm->stRest );
        self::assertSame( 'Foo', $tm->rMatches[ 0 ]->stKey );
        self::assertSame( 'Bar', $tm->rMatches[ 0 ]->stMatch );;
    }


    public function testKey() : void {
        $tn = new TrieNode( null, null );
        $tm = new TrieMatch( $tn, 'rest', [
            new TriePair( 'Foo', 'Bar' ),
            new TriePair( 'Baz', 'Qux' ),
        ] );
        self::assertSame( 'FooBaz', $tm->key() );
    }


    public function testPath() : void {
        $tn = new TrieNode( null, null );
        $tm = new TrieMatch( $tn, 'rest', [
            new TriePair( 'Foo', 'Bar' ),
            new TriePair( 'Baz', 'Qux' ),
        ] );
        self::assertSame( 'BarQux', $tm->path() );
    }


    public function testRest() : void {
        $tn = new TrieNode( null, null );
        $tm = new TrieMatch( $tn, 'Foo', [] );
        self::assertSame( 'Foo', $tm->rest() );
    }


    public function testValue() : void {
        $tn = new TrieNode( 'Foo', null );
        $tm = new TrieMatch( $tn, 'rest', [] );
        self::assertSame( 'Foo', $tm->value() );
    }


}
