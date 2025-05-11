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

        $tnFoo = new TrieNode( 'FOO', null );
        $tsFoo = $walk->append( 'Foo', 'Foo', $tnFoo );
        self::assertSame( $tsFoo, $walk->tsHead );
        self::assertSame( $tsFoo, $walk->tsTail );
        self::assertNull( $tsFoo->tsPrev );
        self::assertNull( $tsFoo->tsNext );

        $tnFoo = new TrieNode( 'BAR', null );
        $ts2 = $walk->append( '$Bar', 'bar', $tnFoo );
        $tnFoo = new TrieNode( 'BAZ', null );
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
        $tn = new TrieNode( 'FOO', null );
        $walk->append( 'Foo', 'Foo', $tn );
        $tn = new TrieNode( 'BAR', null );
        $walk->append( '$Bar', 'bar', $tn );
        $tn = new TrieNode( 'BAZ', null );
        $walk->append( 'Baz', 'Baz', $tn );
        self::assertSame( 'BAZ', $walk->get() );
        $walk->append( 'Qux', 'Qux', new TrieNode( null, null ) );
        self::assertNull( $walk->get() );
    }


    public function testMerge() : void {
        $walk = new TrieWalk();
        $walk->append( 'Foo', 'Foo', new TrieNode( 'FOO', null ) );
        $walk->append( '$Bar', 'bar', new TrieNode( 'BAR', null ) );
        $walk->stRest = '1';

        self::assertSame( 'BAR', $walk->tsTail->tnTo->xValue );

        $walk2 = new TrieWalk();
        $walk2->append( 'Baz', 'Baz', new TrieNode( 'BAZ', null ) );
        $tnQux = $walk2->append( 'Qux', '$Qux', new TrieNode( 'QUX', null ) );
        $walk2->stRest = '2';

        $walk->merge( $walk2 );
        self::assertSame( $tnQux, $walk->tsTail );
        self::assertSame( 'Foo$BarBazQux', $walk->path() );
        self::assertSame( 'QUX', $walk->tsTail->tnTo->xValue );
        self::assertSame( '12', $walk->stRest );
    }


    public function testMergeFromNull() : void {
        $walk = new TrieWalk();
        $walk2 = new TrieWalk();
        $tnFoo = $walk2->append( 'Foo', 'Foo', new TrieNode( 'FOO', null ) );
        $tnBar = $walk2->append( '$Bar', 'bar', new TrieNode( 'BAR', null ) );
        $walk->merge( $walk2 );

        self::assertSame( $tnFoo, $walk->tsHead );
        self::assertSame( $tnBar, $walk->tsTail );
        self::assertSame( 'Foo$Bar', $walk->path() );
        self::assertNull( $walk->tsHead->tsPrev );
        self::assertNull( $walk->tsTail->tsNext );
        self::assertSame( $tnBar, $walk->tsHead->tsNext );
        self::assertSame( $tnFoo, $walk->tsTail->tsPrev );

    }


    public function testPath() : void {
        $walk = new TrieWalk();
        $tn = new TrieNode( 'FOO', null );
        $walk->append( 'Foo', 'Foo', $tn );
        $tn = new TrieNode( 'BAR', null );
        $walk->append( '$Bar', 'bar', $tn );
        $tn = new TrieNode( 'BAZ', null );
        $walk->append( 'Baz', 'Baz', $tn );
        self::assertSame( 'Foo$BarBaz', $walk->path() );
    }


    public function testPrepend() : void {
        $walk = new TrieWalk();
        $tsFoo = $walk->prepend( 'Foo', 'Foo', new TrieNode( 'FOO', null ) );
        self::assertSame( $tsFoo, $walk->tsHead );
        self::assertSame( $tsFoo, $walk->tsTail );
        self::assertNull( $tsFoo->tsPrev );
        self::assertNull( $tsFoo->tsNext );

        $tsBar = $walk->prepend( '$Bar', 'bar', new TrieNode( 'BAR', null ) );
        self::assertSame( $tsBar, $walk->tsHead );
        self::assertSame( $tsFoo, $walk->tsTail );
        self::assertNull( $tsBar->tsPrev );
        self::assertNull( $tsFoo->tsNext );
        self::assertSame( $tsFoo, $tsBar->tsNext );
        self::assertSame( $tsBar, $tsFoo->tsPrev );

    }


    public function testRollback() : void {
        $walk = new TrieWalk();
        $tnFoo = new TrieNode( 'FOO', null );
        $walk->append( 'Foo', 'Foo', $tnFoo );
        $tnBar = new TrieNode( 'BAR', null );
        $tsBar = $walk->append( '$Bar', 'bar', $tnBar );
        $tnBaz = new TrieNode( 'BAZ', null );
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
