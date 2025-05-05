<?php


declare( strict_types = 1 );


use JDWX\Trie\Trie;
use JDWX\Trie\TrieNode;
use PHPUnit\Framework\Attributes\CoversClass;


#[CoversClass( Trie::class )]
final class TrieTestDisabled {


    public function testAddForNoVariables() : void {
        [ $trie, $root ] = $this->newTrie();
        $trie->add( 'foo', 'FOO' );
        $trie->add( 'foo bar', 'BAR' );
        $trie->add( 'foo bar:baz', 'BAZ' );
        $trie->add( 'foo $bar qux', 'QUX' );

        self::assertSame( 'FOO', $root->rConstants[ 'foo' ]->xValue );
        self::assertSame( 'BAR', $root->rConstants[ 'foo' ]->rConstants[ ' ' ]->rConstants[ 'bar' ]->xValue );
        self::assertSame(
            'BAZ',
            $root->rConstants[ 'foo' ]->rConstants[ ' ' ]->rConstants[ 'bar' ]->rConstants[ ':baz' ]->xValue
        );
        self::assertSame(
            'QUX',
            $root->rConstants[ 'foo' ]->rConstants[ ' ' ]->rConstants[ '$bar qux' ]->xValue
        );

    }


    public function testAddWithVariables() : void {
        [ $trie, $root ] = $this->newTrie( true );
        $trie->add( 'foo${bar}baz', 'BAZ' );
        self::assertSame(
            'BAZ',
            $root->rConstants[ 'foo' ]->rVariables[ '$bar' ]->rConstants[ 'baz' ]->xValue
        );
    }


    /**
     * @return void
     * @noinspection SpellCheckingInspection
     */
    public function testArrayAccess() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'bar' ] = $tnBar;
        $tnBar->rConstants[ 'baz' ] = $tnBaz;

        self::assertSame( 'FOO', $trie[ 'foo' ] );
        self::assertSame( 'BAR', $trie[ 'foobar' ] );
        self::assertSame( 'BAZ', $trie[ 'foobarbaz' ] );
        self::assertNull( $trie[ 'foobarbazqux' ] );
        self::assertNull( $trie[ 'foobarqux' ] );
        self::assertNull( $trie[ 'fooqux' ] );

        self::assertTrue( isset( $trie[ 'foo' ] ) );
        self::assertTrue( isset( $trie[ 'foobar' ] ) );
        self::assertTrue( isset( $trie[ 'foobarbaz' ] ) );
        self::assertFalse( isset( $trie[ 'foobarbazqux' ] ) );
        self::assertFalse( isset( $trie[ 'foobarqux' ] ) );
        self::assertFalse( isset( $trie[ 'fooqux' ] ) );

        $trie[ 'foo' ] = 'OOF';
        self::assertSame( 'OOF', $trie[ 'foo' ] );
        $trie[ 'foobar' ] = 'RAB';
        self::assertSame( 'RAB', $trie[ 'foobar' ] );
        $trie[ 'foobarbaz' ] = 'ZAB';
        self::assertSame( 'ZAB', $trie[ 'foobarbaz' ] );
        $trie[ 'fooqux' ] = 'QUX';
        self::assertSame( 'QUX', $trie[ 'fooqux' ] );
        self::assertSame( 'RAB', $trie[ 'foobar' ] );

        unset( $trie[ 'foobar' ] );
        self::assertNull( $trie[ 'foobar' ] );
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertFalse( isset( $trie[ 'foobar' ] ) );
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertTrue( isset( $trie[ 'foobarbaz' ] ) );
    }


    public function testExtractVariableValue() : void {
        [ $stMatch, $stPath ] = Trie::extractVariableValue( 'FooBarBaz Qux' );
        self::assertSame( 'FooBarBaz', $stMatch );
        self::assertSame( ' Qux', $stPath );

        [ $stMatch, $stPath ] = Trie::extractVariableValue( 'FooBarBaz' );
        self::assertSame( 'FooBarBaz', $stMatch );
        self::assertSame( '', $stPath );

        [ $stMatch, $stPath ] = Trie::extractVariableValue( '!FooBarBaz' );
        self::assertNull( $stMatch );
        self::assertSame( '!FooBarBaz', $stPath );
    }


    /**
     * @return void
     * @noinspection SpellCheckingInspection
     */
    public function testGet() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'bar' ] = $tnBar;
        $tnBar->rConstants[ 'baz' ] = $tnBaz;

        self::assertSame( 'FOO', $trie->get( 'foo' ) );
        self::assertSame( 'BAR', $trie->get( 'foobar' ) );
        $r = [];
        self::assertSame( 'BAZ', $trie->get( 'foobarbaz', $r ) );
        self::assertEmpty( $r );
        self::assertNull( $trie->get( 'foobarbazqux' ) );
        self::assertNull( $trie->get( 'fooqux' ) );
    }


    /**
     * @return void
     * @noinspection SpellCheckingInspection
     */
    public function testGetForAmbiguousIntermediateVariable() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnQux = new TrieNode( 'QUX' );
        $tnQuux = new TrieNode( 'QUUX' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rVariables[ '$bar' ] = $tnBar;
        $tnFoo->rVariables[ '$baz' ] = $tnBaz;
        $tnBar->rConstants[ ' qux' ] = $tnQux;
        $tnBaz->rConstants[ ' qux' ] = $tnQuux;

        $r = [];
        self::expectException( RuntimeException::class );
        $trie->get( 'foocorge qux', $r );
    }


    public function testGetForAmbiguousTerminalVariable() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rVariables[ '$bar' ] = $tnBar;
        $tnFoo->rVariables[ '$baz' ] = $tnBaz;

        $r = [];
        self::expectException( RuntimeException::class );
        $trie->get( 'fooqux', $r );
    }


    /**
     * @noinspection SpellCheckingInspection
     * @return void
     */
    public function testGetForInvalidVariableValue() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rVariables[ '$bar' ] = $tnBar;
        $tnBar->rConstants[ ' baz' ] = $tnBaz;

        $r = [];
        self::assertNull( $trie->get( 'foo quxbaz', $r ) );
    }


    /**
     * @return void
     * @noinspection SpellCheckingInspection
     */
    public function testGetForMixedVariableAndStatic() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'bar' ] = $tnBar;
        $tnFoo->rVariables[ '$baz' ] = $tnBaz;

        self::assertSame( 'FOO', $trie->get( 'foo' ) );
        self::assertSame( 'BAR', $trie->get( 'foobar' ) );
        self::assertSame( 'BAZ', $trie->get( 'fooqux' ) );

        $tnQux = new TrieNode( 'QUX' );
        $tnQuux = new TrieNode( 'QUUX' );
        $tnBar->rConstants[ ' qux' ] = $tnQux;
        $tnBaz->rConstants[ ' qux' ] = $tnQuux;

        self::assertSame( 'QUX', $trie->get( 'foobar qux' ) );
        self::assertSame( 'QUUX', $trie->get( 'foocorge qux' ) );
    }


    public function testGetForNoMatchAfterVariable() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rVariables[ '$bar' ] = $tnBar;
        $tnBar->rConstants[ ' baz' ] = $tnBaz;

        $r = [];
        self::assertNull( $trie->get( 'fooqux quux', $r ) );
    }


    /**
     * @noinspection SpellCheckingInspection
     * @return void
     */
    public function testGetForPastEnd() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'bar' ] = $tnBar;
        $tnBar->rConstants[ 'baz' ] = $tnBaz;

        self::assertSame( 'FOO', $trie->get( 'foo' ) );
        self::assertSame( 'BAR', $trie->get( 'foobar' ) );
        self::assertSame( 'BAZ', $trie->get( 'foobarbaz' ) );
        self::assertNull( $trie->get( 'foobarbazqux' ) );
    }


    /**
     * @return void
     * @noinspection SpellCheckingInspection
     */
    public function testGetForUnambiguousTerminalVariable() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnQux = new TrieNode( 'QUX' );
        $tnQuux = new TrieNode( 'QUUX' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rVariables[ '$bar' ] = $tnBar;
        $tnFoo->rVariables[ '$baz' ] = $tnBaz;
        $tnBar->rConstants[ ' qux' ] = $tnQux;
        $tnBaz->rConstants[ ' quux' ] = $tnQuux;

        $r = [];
        self::assertSame( 'QUX', $trie->get( 'foocorge qux', $r ) );
        self::assertCount( 1, $r );
        assert( is_array( $r ) );
        self::assertSame( 'corge', $r[ '$bar' ] );
    }


    public function testGetWithVariables() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rVariables[ '$bar' ] = $tnBar;
        $tnBar->rConstants[ ' baz' ] = $tnBaz;

        $r = [];
        self::assertSame( 'FOO', $trie->get( 'foo', $r ) );
        self::assertEmpty( $r );

        $r = [];
        self::assertSame( 'BAR', $trie->get( 'fooqux', $r ) );
        self::assertCount( 1, $r );
        assert( is_array( $r ) );
        self::assertSame( 'qux', $r[ '$bar' ] );

        $r = [];
        self::assertSame( 'BAZ', $trie->get( 'fooqux baz', $r ) );
        self::assertCount( 1, $r );
        assert( is_array( $r ) );
        self::assertSame( 'qux', $r[ '$bar' ] );
    }


    /**
     * @return void
     * @noinspection SpellCheckingInspection
     */
    public function testHas() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'bar' ] = $tnBar;
        $tnBar->rConstants[ 'baz' ] = $tnBaz;

        self::assertTrue( $trie->has( 'foo' ) );
        self::assertTrue( $trie->has( 'foobar' ) );
        self::assertTrue( $trie->has( 'foobarbaz' ) );
        self::assertFalse( $trie->has( 'foobarqux' ) );
        self::assertFalse( $trie->has( 'foobarbazqux' ) );
        self::assertFalse( $trie->has( 'fooqux' ) );
    }


    public function testHasWithVariables() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $root->rConstants[ 'foo' ] = new TrieNode( 'FOO' );
        $root->rConstants[ 'foo' ]->rVariables[ '$bar' ] = new TrieNode( 'BAR' );
        $root->rConstants[ 'foo' ]->rVariables[ '$bar' ]->rConstants[ ' baz' ] = new TrieNode( 'BAZ' );
        $root->rConstants[ 'foo' ]->rVariables[ '$bar' ]->rConstants[ ' qux' ] = new TrieNode( 'QUX' );

        self::assertSame( 'FOO', $trie->get( 'foo' ) );
        self::assertSame( 'BAR', $trie->get( 'fooqux' ) );
        self::assertSame( 'BAZ', $trie->get( 'fooqux baz' ) );
        self::assertTrue( $trie->has( 'foo${bar} baz' ) );
        self::assertTrue( $trie->has( 'foo${bar} qux' ) );
        self::assertFalse( $trie->has( 'foo${bar} quux' ) );
        self::assertFalse( $trie->has( 'fooqux' ) );

        self::assertFalse( $trie->has( 'foo${bar} baz', true ) );
        self::assertFalse( $trie->has( 'foo${bar} qux', true ) );
        self::assertFalse( $trie->has( 'foo${bar} quux', true ) );
        self::assertTrue( $trie->has( 'fooqux', true ) );

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


    /**
     * @return void
     * @noinspection SpellCheckingInspection
     */
    public function testSet() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $trie->set( 'foo', 'FOO' );
        $trie->set( 'foobar', 'BAR' );
        $trie->set( 'foobarbaz', 'BAZ' );

        self::assertSame( 'FOO', $root->rConstants[ 'foo' ]->xValue );
        $trie->set( 'foo', 'OOF' );
        self::assertSame( 'OOF', $root->rConstants[ 'foo' ]->xValue );

        self::assertSame( 'BAR', $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->xValue );
        $trie->set( 'foobar', 'RAB' );
        self::assertSame( 'RAB', $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->xValue );

        self::assertSame( 'BAZ', $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->rConstants[ 'baz' ]->xValue );
        $trie->set( 'foobarbaz', 'ZAB' );
        self::assertSame( 'ZAB', $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->rConstants[ 'baz' ]->xValue );

        $trie->set( 'foo${bar}qux', 'QUX' );
        self::assertSame(
            'QUX',
            $root->rConstants[ 'foo' ]->rVariables[ '$bar' ]->rConstants[ 'qux' ]->xValue
        );
        self::assertSame(
            'ZAB',
            $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->rConstants[ 'baz' ]->xValue
        );
    }


    public function testUnsetForNoPrune() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnQux = new TrieNode( 'QUX' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'bar' ] = $tnBar;
        $tnBar->rConstants[ 'baz' ] = $tnBaz;
        $tnBar->rVariables[ '$qux' ] = $tnQux;

        $trie->unset( 'foobar' );
        self::assertNull( $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->xValue );
        self::assertSame( 'BAZ', $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->rConstants[ 'baz' ]->xValue );
        self::assertSame( 'QUX', $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->rVariables[ '$qux' ]->xValue );
    }


    public function testUnsetForPrune() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnQux = new TrieNode( 'QUX' );
        $root->rConstants[ 'foo' ] = $tnFoo;
        $tnFoo->rConstants[ 'bar' ] = $tnBar;
        $tnBar->rConstants[ 'baz' ] = $tnBaz;
        $tnBar->rVariables[ '$qux' ] = $tnQux;

        $trie->unset( 'foobar', true );
        self::assertNull( $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->xValue );
        self::assertEmpty( $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->rConstants );
        self::assertEmpty( $root->rConstants[ 'foo' ]->rConstants[ 'bar' ]->rVariables );
    }


    /** @return list<Trie|TrieNode> */
    private function newTrie( bool $i_bAllowVariables = false ) : array {
        $trie = new class( $i_bAllowVariables ) extends Trie {


            public function root() : TrieNode {
                return $this->tnRoot;
            }


        };
        return [ $trie, $trie->root() ];
    }


}
