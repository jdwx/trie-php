<?php


declare( strict_types = 1 );


use JDWX\Trie\Trie;
use JDWX\Trie\TrieNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( Trie::class )]
final class TrieTest extends TestCase {


    public function testAddForNoVariables() : void {
        [ $trie, $root ] = $this->newTrie();
        $trie->add( 'foo', 'FOO' );
        $trie->add( 'foo bar', 'BAR' );
        $trie->add( 'foo bar:baz', 'BAZ' );
        $trie->add( 'foo $bar qux', 'QUX' );

        self::assertSame( 'FOO', $root->rChildren[ 'foo' ]->xValue );
        self::assertSame( 'BAR', $root->rChildren[ 'foo' ]->rChildren[ ' ' ]->rChildren[ 'bar' ]->xValue );
        self::assertSame(
            'BAZ',
            $root->rChildren[ 'foo' ]->rChildren[ ' ' ]->rChildren[ 'bar' ]->rChildren[ ':baz' ]->xValue
        );
        self::assertSame(
            'QUX',
            $root->rChildren[ 'foo' ]->rChildren[ ' ' ]->rChildren[ '$bar qux' ]->xValue
        );

    }


    public function testAddWithVariables() : void {
        [ $trie, $root ] = $this->newTrie( true );
        $trie->add( 'foo${bar}baz', 'BAZ' );
        self::assertSame(
            'BAZ',
            $root->rChildren[ 'foo' ]->rVariableChildren[ '$bar' ]->rChildren[ 'baz' ]->xValue
        );
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


    /** @noinspection SpellCheckingInspection */
    public function testGet() : void {
        [ $trie, $root ] = $this->newTrie();
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rChildren[ 'bar' ] = $tnBar;
        $tnBar->rChildren[ 'baz' ] = $tnBaz;

        self::assertSame( 'FOO', $trie->get( 'foo' ) );
        self::assertSame( 'BAR', $trie->get( 'foobar' ) );
        self::assertSame( 'BAZ', $trie->get( 'foobarbaz' ) );
        self::assertNull( $trie->get( 'foobarbazqux' ) );
        self::assertNull( $trie->get( 'fooqux' ) );
    }


    /** @noinspection SpellCheckingInspection */
    public function testGetForAmbiguousIntermediateVariable() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnQux = new TrieNode( 'QUX' );
        $tnQuux = new TrieNode( 'QUUX' );
        $root->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rVariableChildren[ '$bar' ] = $tnBar;
        $tnFoo->rVariableChildren[ '$baz' ] = $tnBaz;
        $tnBar->rChildren[ ' qux' ] = $tnQux;
        $tnBaz->rChildren[ ' qux' ] = $tnQuux;

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
        $root->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rVariableChildren[ '$bar' ] = $tnBar;
        $tnFoo->rVariableChildren[ '$baz' ] = $tnBaz;

        $r = [];
        self::expectException( RuntimeException::class );
        $trie->get( 'fooqux', $r );
    }


    public function testGetForNoMatchAfterVariable() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rVariableChildren[ '$bar' ] = $tnBar;
        $tnBar->rChildren[ ' baz' ] = $tnBaz;

        $r = [];
        self::assertNull( $trie->get( 'fooqux quux', $r ) );
    }


    /** @noinspection SpellCheckingInspection */
    public function testGetForUnambiguousTerminalVariable() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnQux = new TrieNode( 'QUX' );
        $tnQuux = new TrieNode( 'QUUX' );
        $root->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rVariableChildren[ '$bar' ] = $tnBar;
        $tnFoo->rVariableChildren[ '$baz' ] = $tnBaz;
        $tnBar->rChildren[ ' qux' ] = $tnQux;
        $tnBaz->rChildren[ ' quux' ] = $tnQuux;

        $r = [];
        self::assertSame( 'QUX', $trie->get( 'foocorge qux', $r ) );
        self::assertCount( 1, $r );
        assert( is_array( $r ) );
        self::assertSame( 'corge', $r[ '$bar' ] );
    }


    /** @noinspection SpellCheckingInspection */
    public function testGetWithInvalidVariableValue() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rVariableChildren[ '$bar' ] = $tnBar;
        $tnBar->rChildren[ ' baz' ] = $tnBaz;

        $r = [];
        self::assertNull( $trie->get( 'foo quxbaz', $r ) );
    }


    public function testGetWithVariables() : void {
        [ $trie, $root ] = $this->newTrie( true );
        assert( $root instanceof TrieNode );
        assert( $trie instanceof Trie );
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $root->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rVariableChildren[ '$bar' ] = $tnBar;
        $tnBar->rChildren[ ' baz' ] = $tnBaz;

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
