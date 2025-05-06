<?php


declare( strict_types = 1 );


use JDWX\Trie\Trie;
use JDWX\Trie\TrieNode;
use JDWX\Trie\TrieNodeNavigator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( Trie::class )]
final class TrieTest extends TestCase {


    public function testAdd() : void {
        [ $trie, $root ] = $this->newTrie( true );
        $trie->add( 'Foo${Bar}Baz', 'BAZ' );
        self::assertSame(
            'BAZ',
            $root->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ]->xValue
        );
    }


    public function testArrayAccess() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNodeNavigator );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNodeNavigator( 'FOO' );
        $tnBar = new TrieNodeNavigator( 'BAR' );
        $tnBaz = new TrieNodeNavigator( 'BAZ' );
        $root->rConstants[ 'Foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'Bar' ] = $tnBar;
        $tnBar->rConstants[ 'Baz' ] = $tnBaz;

        self::assertSame( 'FOO', $trie[ 'Foo' ] );
        self::assertSame( 'BAR', $trie[ 'FooBar' ] );
        self::assertSame( 'BAZ', $trie[ 'FooBarBaz' ] );
        self::assertNull( $trie[ 'FooBarBazQux' ] );
        self::assertNull( $trie[ 'FooBarQux' ] );
        self::assertNull( $trie[ 'FooQux' ] );

        self::assertTrue( isset( $trie[ 'Foo' ] ) );
        self::assertTrue( isset( $trie[ 'FooBar' ] ) );
        self::assertTrue( isset( $trie[ 'FooBarBaz' ] ) );
        self::assertFalse( isset( $trie[ 'FooBarBazQux' ] ) );
        self::assertFalse( isset( $trie[ 'FooBarQux' ] ) );
        self::assertFalse( isset( $trie[ 'FooQux' ] ) );

        $trie[ 'Foo' ] = 'OOF';
        self::assertSame( 'OOF', $trie[ 'Foo' ] );
        $trie[ 'FooBar' ] = 'RAB';
        self::assertSame( 'RAB', $trie[ 'FooBar' ] );
        $trie[ 'FooBarBaz' ] = 'ZAB';
        self::assertSame( 'ZAB', $trie[ 'FooBarBaz' ] );
        $trie[ 'FooQux' ] = 'QUX';
        self::assertSame( 'QUX', $trie[ 'FooQux' ] );
        self::assertSame( 'RAB', $trie[ 'FooBar' ] );

        unset( $trie[ 'FooBar' ] );
        self::assertNull( $trie[ 'FooBar' ] );
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertFalse( isset( $trie[ 'FooBar' ] ) );
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertTrue( isset( $trie[ 'FooBarBaz' ] ) );
    }


    public function testGet() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNodeNavigator );
        assert( $trie instanceof Trie );

        $root->add( 'FooBarBaz', 'BAZ' );

        self::assertNull( $trie->get( 'Foo' ) );
        self::assertNull( $trie->get( 'FooBar' ) );
        self::assertSame( 'BAZ', $trie->get( 'FooBarBaz' ) );
        self::assertNull( $trie->get( 'FooBarBazQux' ) );
    }


    public function testHas() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNodeNavigator );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNodeNavigator( 'FOO' );
        $tnBar = new TrieNodeNavigator( 'BAR' );
        $tnBaz = new TrieNodeNavigator( 'BAZ' );
        $root->rConstants[ 'Foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'Bar' ] = $tnBar;
        $tnBar->rConstants[ 'Baz' ] = $tnBaz;

        self::assertTrue( $trie->has( 'Foo' ) );
        self::assertTrue( $trie->has( 'FooBar' ) );
        self::assertTrue( $trie->has( 'FooBarBaz' ) );
        self::assertFalse( $trie->has( 'FooBarQux' ) );
        self::assertFalse( $trie->has( 'FooBarBazQux' ) );
        self::assertFalse( $trie->has( 'FooQux' ) );
    }


    public function testOffsetExistsForNotString() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        self::expectException( InvalidArgumentException::class );
        /** @phpstan-ignore-next-line */
        $x = isset( $trie[ 1 ] );
        unset( $x );
    }


    public function testOffsetGetForNotString() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        self::expectException( InvalidArgumentException::class );
        /** @phpstan-ignore-next-line */
        $x = $trie[ 1 ];
        unset( $x );
    }


    public function testOffsetSetForNotString() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        self::expectException( InvalidArgumentException::class );
        /** @phpstan-ignore-next-line */
        $trie[ 1 ] = 'FOO';
    }


    public function testOffsetUnsetForNotString() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        self::expectException( InvalidArgumentException::class );
        /** @phpstan-ignore-next-line */
        unset( $trie[ 1 ] );
    }


    public function testSet() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $trie->set( 'Foo', 'FOO' );
        $trie->set( 'FooBar', 'BAR' );
        $trie->set( 'FooBarBaz', 'BAZ' );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r ) );
        $trie->set( 'Foo', 'OOF' );
        self::assertSame( 'OOF', $root->get( 'Foo', $r ) );

        self::assertSame( 'BAR', $root->get( 'FooBar', $r ) );
        $trie->set( 'FooBar', 'RAB' );
        self::assertSame( 'RAB', $root->get( 'FooBar', $r ) );

        self::assertSame( 'BAZ', $root->get( 'FooBarBaz', $r ) );
        $trie->set( 'FooBarBaz', 'ZAB' );
        self::assertSame( 'ZAB', $root->get( 'FooBarBaz', $r ) );

        $trie->set( 'Foo${Bar}Qux', 'QUX' );
        self::assertSame(
            'QUX',
            $root->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Qux' ]->xValue
        );
        self::assertSame(
            'ZAB',
            $root->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->xValue
        );
    }


    public function testUnset() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo', 'FOO' );
        $root->add( 'FooBar', 'BAR' );
        $root->add( 'FooBarBaz', 'BAZ' );

        self::assertSame( 'FOO', $trie->get( 'Foo' ) );
        self::assertSame( 'BAR', $trie->get( 'FooBar' ) );
        self::assertSame( 'BAZ', $trie->get( 'FooBarBaz' ) );
        $trie->unset( 'FooBar' );
        self::assertSame( 'FOO', $trie->get( 'Foo' ) );
        self::assertNull( $trie->get( 'FooBar' ) );
        self::assertSame( 'BAZ', $trie->get( 'FooBarBaz' ) );
    }


    /** @return list<Trie|TrieNodeNavigator> */
    private function newTrie( bool $i_bAllowVariables = false ) : array {
        $trie = new class( $i_bAllowVariables ) extends Trie {


            public function root() : TrieNodeNavigator {
                return $this->tnRoot;
            }


        };
        return [ $trie, $trie->root() ];
    }


}
