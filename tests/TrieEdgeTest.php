<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieEdge;
use JDWX\Trie\TrieNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieEdge::class )]
final class TrieEdgeTest extends TestCase {


    public function testConstruct() : void {
        $tn = new TrieNode( null, null );
        $tm = new TrieEdge( 'Foo', 'Bar', $tn );
        self::assertSame( 'Foo', $tm->stEdge );
        self::assertSame( 'Bar', $tm->stMatch );
        self::assertSame( $tn, $tm->tnTo );
    }


}
