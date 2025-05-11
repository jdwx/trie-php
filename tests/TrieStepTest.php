<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieNode;
use JDWX\Trie\TrieStep;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieStep::class )]
final class TrieStepTest extends TestCase {


    public function testConstructor() : void {
        $tn = new TrieNode( 'FOO', null );
        $ts = new TrieStep( '$Foo', 'foo', $tn );
        self::assertSame( '$Foo', $ts->stEdge );
        self::assertSame( 'foo', $ts->stMatch );
        self::assertInstanceOf( TrieNode::class, $ts->tnTo );
        self::assertSame( $tn, $ts->tnTo );
        self::assertNull( $ts->tsPrev );
        self::assertNull( $ts->tsNext );
    }


}
