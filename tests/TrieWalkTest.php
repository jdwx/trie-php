<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieNode;
use JDWX\Trie\TrieWalk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieWalk::class )]
final class TrieWalkTest extends TestCase {


    public function testAppend() : void {
        $walk = new TrieWalk();
        self::assertNull( $walk->tsHead );
        self::assertNull( $walk->tsTail );

        $tnFoo = new TrieNode( 'FOO' );
        $tsFoo = $walk->append( 'Foo', 'Foo', $tnFoo );
        self::assertSame( $tsFoo, $walk->tsHead );
        self::assertSame( $tsFoo, $walk->tsTail );
        self::assertNull( $tsFoo->tsPrev );
        self::assertNull( $tsFoo->tsNext );

        $tnFoo = new TrieNode( 'BAR' );
        $ts2 = $walk->append( '$Bar', 'bar', $tnFoo );
        $tnFoo = new TrieNode( 'BAZ' );
        $ts3 = $walk->append( 'Baz', 'Baz', $tnFoo );
        self::assertSame( $tsFoo, $walk->tsHead );
        self::assertSame( $ts2, $walk->tsHead->tsNext );
        self::assertSame( $ts3, $walk->tsHead->tsNext->tsNext );
        self::assertNull( $walk->tsHead->tsNext->tsNext->tsNext );
        self::assertSame( $ts3, $walk->tsTail );
        self::assertSame( $ts2, $walk->tsTail->tsPrev );
        self::assertSame( $tsFoo, $walk->tsTail->tsPrev->tsPrev );
        self::assertNull( $walk->tsTail->tsPrev->tsPrev->tsPrev );
    }


    public function testGet() : void {
        $walk = new TrieWalk();
        self::assertNull( $walk->get() );
        $tn = new TrieNode( 'FOO' );
        $walk->append( 'Foo', 'Foo', $tn );
        $tn = new TrieNode( 'BAR' );
        $walk->append( '$Bar', 'bar', $tn );
        $tn = new TrieNode( 'BAZ' );
        $walk->append( 'Baz', 'Baz', $tn );
        self::assertSame( 'BAZ', $walk->get() );
        $walk->append( 'Qux', 'Qux', new TrieNode() );
        self::assertNull( $walk->get() );
    }


    public function testMerge() : void {
        $walk = new TrieWalk();
        $walk->append( 'Foo', 'Foo', new TrieNode( 'FOO' ) );
        $walk->append( '$Bar', 'bar', new TrieNode( 'BAR' ) );
        $walk->stRest = '1';

        $walk2 = new TrieWalk();
        $walk->append( 'Baz', 'Baz', new TrieNode( 'BAZ' ) );
        $walk->append( 'Qux', '$Qux', new TrieNode( 'QUX' ) );
        $walk2->stRest = '2';

        $walk->merge( $walk2 );
        self::assertSame( 'Foo$BarBazQux', $walk->path() );
        self::assertSame( '12', $walk->stRest );
    }


    public function testPath() : void {
        $walk = new TrieWalk();
        $tn = new TrieNode( 'FOO' );
        $walk->append( 'Foo', 'Foo', $tn );
        $tn = new TrieNode( 'BAR' );
        $walk->append( '$Bar', 'bar', $tn );
        $tn = new TrieNode( 'BAZ' );
        $walk->append( 'Baz', 'Baz', $tn );
        self::assertSame( 'Foo$BarBaz', $walk->path() );
    }


    public function testPrepend() : void {
        $walk = new TrieWalk();
        $tsFoo = $walk->prepend( 'Foo', 'Foo', new TrieNode( 'FOO' ) );
        self::assertSame( $tsFoo, $walk->tsHead );
        self::assertSame( $tsFoo, $walk->tsTail );
        self::assertNull( $tsFoo->tsPrev );
        self::assertNull( $tsFoo->tsNext );

        $tsBar = $walk->prepend( '$Bar', 'bar', new TrieNode( 'BAR' ) );
        self::assertSame( $tsBar, $walk->tsHead );
        self::assertSame( $tsFoo, $walk->tsTail );
        self::assertNull( $tsBar->tsPrev );
        self::assertNull( $tsFoo->tsNext );
        self::assertSame( $tsFoo, $tsBar->tsNext );
        self::assertSame( $tsBar, $tsFoo->tsPrev );

    }


    public function testRollback() : void {
        $walk = new TrieWalk();
        $tnFoo = new TrieNode( 'FOO' );
        $walk->append( 'Foo', 'Foo', $tnFoo );
        $tnBar = new TrieNode( 'BAR' );
        $tsBar = $walk->append( '$Bar', 'bar', $tnBar );
        $tnBaz = new TrieNode( 'BAZ' );
        $tsBaz = $walk->append( 'Baz', 'Baz', $tnBaz );
        $walk->stRest = '!';
        self::assertSame( $tsBaz, $walk->tsTail );
        $walk->rollback();
        self::assertSame( $tsBaz, $walk->tsTail );
        self::assertSame( '!', $walk->stRest );

        $tnBaz->xValue = null;
        $walk->rollback();
        self::assertSame( $tsBar, $walk->tsTail );
        self::assertSame( 'Baz!', $walk->stRest );

        $tnBar->xValue = null;
        $tnFoo->xValue = null;
        $walk->rollback();
        self::assertNull( $walk->tsTail );
        self::assertNull( $walk->tsHead );
        self::assertSame( 'Foo$BarBaz!', $walk->stRest );

        $walk->rollback();
        self::assertNull( $walk->tsTail );
        self::assertNull( $walk->tsHead );
        self::assertSame( 'Foo$BarBaz!', $walk->stRest );
    }


}
