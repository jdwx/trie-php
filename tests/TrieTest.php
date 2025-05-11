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
        $tnFoo = new TrieNodeNavigator( 'FOO', null );
        $tnBar = new TrieNodeNavigator( 'BAR', null );
        $tnBaz = new TrieNodeNavigator( 'BAZ', null );
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

        $root->add( 'FooBarBaz', 'BAZ', false, false );

        self::assertNull( $trie->get( 'Foo' ) );
        self::assertNull( $trie->get( 'FooBar' ) );
        self::assertSame( 'BAZ', $trie->get( 'FooBarBaz' ) );
        self::assertNull( $trie->get( 'FooBarBazQux' ) );
    }


    public function testGetForRepeatVariables() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNodeNavigator );
        assert( $trie instanceof Trie );
        $root->add( 'Foo${Bar}Baz${Bar}', 'FOO', true, false );

        $r = [];
        self::assertSame( 'FOO', $trie->get( 'FooQuxBazQuux', $r ) );
        assert( is_array( $r ) );
        self::assertIsArray( $r[ '$Bar' ] );
        self::assertSame( 'Qux', $r[ '$Bar' ][ 0 ] );
        self::assertSame( 'Quux', $r[ '$Bar' ][ 1 ] );
    }


    public function testHas() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNodeNavigator );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNodeNavigator( 'FOO', null );
        $tnBar = new TrieNodeNavigator( 'BAR', null );
        $tnBaz = new TrieNodeNavigator( 'BAZ', null );
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


    public function testMatch() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNodeNavigator );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNodeNavigator( 'FOO', null );
        $tnBar = new TrieNodeNavigator( 'BAR', null );
        $tnBaz = new TrieNodeNavigator( 'BAZ', null );
        $root->rConstants[ 'Foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'Bar' ] = $tnBar;
        $tnBar->rConstants[ 'Baz' ] = $tnBaz;

        $r = iterator_to_array( $trie->match( 'FooBarBazQux' ), false );
        self::assertCount( 3, $r );

        self::assertSame( 'FOO', $r[ 0 ]->value() );
        self::assertSame( 'BAR', $r[ 1 ]->value() );
        self::assertSame( 'BAZ', $r[ 2 ]->value() );

        self::assertSame( 'Foo', $r[ 0 ]->path() );
        self::assertSame( 'FooBar', $r[ 1 ]->path() );
        self::assertSame( 'FooBarBaz', $r[ 2 ]->path() );

        self::assertSame( 'BarBazQux', $r[ 0 ]->rest() );
        self::assertSame( 'BazQux', $r[ 1 ]->rest() );
        self::assertSame( 'Qux', $r[ 2 ]->rest() );
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


    public function testRestExForExtra() : void {
        [ $trie, $root ] = $this->newTrie( true, true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo', 'FOO', false, false );
        $root->add( 'FooBar', 'BAR', false, false );
        $root->add( 'FooBarBaz', 'BAZ', false, false );

        self::assertSame( 'BAZ', $trie[ 'FooBarBazQux' ] );
        self::assertSame( 'Qux', $trie->restEx() );
        self::assertSame( 'Qux', $trie->restEx( 'Quux' ) );

        self::assertSame( 'BAR', $trie[ 'FooBar' ] );
        self::assertSame( '', $trie->restEx() );
    }


    public function testRestExForNoExtra() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo', 'FOO', false, false );
        self::assertSame( 'FOO', $trie[ 'Foo' ] );
        self::expectException( RuntimeException::class );
        $trie->restEx();
    }


    public function testRestForExtra() : void {
        [ $trie, $root ] = $this->newTrie( true, true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo', 'FOO', false, false );
        $root->add( 'FooBar', 'BAR', false, false );
        $root->add( 'FooBarBaz', 'BAZ', false, false );

        self::assertSame( 'BAZ', $trie[ 'FooBarBazQux' ] );
        self::assertSame( 'Qux', $trie->rest() );
        self::assertSame( 'Qux', $trie->rest( 'Quux' ) );
        self::assertNull( $trie[ 'Corge' ] );
    }


    public function testRestForNoExtra() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo', 'FOO', false, false );
        $root->add( 'FooBar', 'BAR', false, false );
        $root->add( 'FooBarBaz', 'BAZ', false, false );

        self::assertNull( $trie[ 'FooBarBazQux' ] );
        self::assertNull( $trie->rest() );
        self::assertSame( 'Qux', $trie->rest( 'Qux' ) );
    }


    public function testSet() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $trie->set( 'Foo', 'FOO' );
        $trie->set( 'FooBar', 'BAR' );
        $trie->set( 'FooBarBaz', 'BAZ' );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, false ) );
        $trie->set( 'Foo', 'OOF' );
        self::assertSame( 'OOF', $root->get( 'Foo', $r, false ) );

        self::assertSame( 'BAR', $root->get( 'FooBar', $r, false ) );
        $trie->set( 'FooBar', 'RAB' );
        self::assertSame( 'RAB', $root->get( 'FooBar', $r, false ) );

        self::assertSame( 'BAZ', $root->get( 'FooBarBaz', $r, false ) );
        $trie->set( 'FooBarBaz', 'ZAB' );
        self::assertSame( 'ZAB', $root->get( 'FooBarBaz', $r, false ) );

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
        $root->add( 'Foo', 'FOO', false, false );
        $root->add( 'FooBar', 'BAR', false, false );
        $root->add( 'FooBarBaz', 'BAZ', false, false );

        self::assertSame( 'FOO', $trie->get( 'Foo' ) );
        self::assertSame( 'BAR', $trie->get( 'FooBar' ) );
        self::assertSame( 'BAZ', $trie->get( 'FooBarBaz' ) );
        $trie->unset( 'FooBar' );
        self::assertSame( 'FOO', $trie->get( 'Foo' ) );
        self::assertNull( $trie->get( 'FooBar' ) );
        self::assertSame( 'BAZ', $trie->get( 'FooBarBaz' ) );
    }


    public function testVar() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo${Bar}Baz', 'BAZ', true, false );
        self::assertSame( 'BAZ', $trie[ 'FooQuxBaz' ] );
        self::assertSame( 'Qux', $trie->var( '$Bar' ) );
        self::assertSame( 'Corge', $trie->var( '$Quux', 'Corge' ) );
        self::assertNull( $trie->var( '$Quux' ) );
    }


    public function testVarEx() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo${Bar}Baz', 'BAZ', true, false );
        self::assertSame( 'BAZ', $trie[ 'FooQuxBaz' ] );
        self::assertSame( 'Qux', $trie->varEx( '$Bar' ) );
        self::assertSame( 'Corge', $trie->varEx( '$Quux', 'Corge' ) );
        self::expectException( InvalidArgumentException::class );
        $trie->varEx( '$Quux' );
    }


    public function testVariables() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->add( 'Foo${Bar}Baz', 'BAZ', true, false );
        self::assertSame( 'BAZ', $trie[ 'FooQuxBaz' ] );
        $r = $trie->variables();
        self::assertCount( 1, $r );
        self::assertSame( 'Qux', $r[ '$Bar' ] );
    }


    /** @return list<Trie|TrieNodeNavigator> */
    private function newTrie( bool $i_bAllowVariables = false, bool $i_bAllowExtra = false ) : array {
        $trie = new class( $i_bAllowVariables, $i_bAllowExtra ) extends Trie {


            public function root() : TrieNodeNavigator {
                return $this->tnRoot;
            }


        };
        return [ $trie, $trie->root() ];
    }


}
