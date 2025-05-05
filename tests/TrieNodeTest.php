<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieNode::class )]
final class TrieNodeTest extends TestCase {


    public function testAddForCommonPrefix() : void {
        $node = new TrieNode();
        $node->rChildren[ 'foobar' ] = new TrieNode( 'baz' );
        $tn = $node->add( 'fooqux', 'quux' );
        self::assertSame( 'quux', $tn->xValue );
        self::assertSame( $tn, $node->rChildren[ 'foo' ]->rChildren[ 'qux' ] );
        self::assertSame( 'baz', $node->rChildren[ 'foo' ]->rChildren[ 'bar' ]->xValue );
    }


    public function testAddForDisallowedVariable() : void {
        $node = new TrieNode();
        $tn = $node->add( 'foo$bar', 'baz' );
        self::assertSame( 'baz', $tn->xValue );
        self::assertSame( $tn, $node->rChildren[ 'foo$bar' ] );
    }


    public function testAddForExistingNodePrefixMatch() : void {
        $node = new TrieNode();
        $node->rChildren[ 'foo' ] = new TrieNode( 'bar' );
        $tn = $node->add( 'fooqux', 'baz' );
        self::assertSame( 'baz', $tn->xValue );
        self::assertSame( 'bar', $node->rChildren[ 'foo' ]->xValue );
        self::assertArrayHasKey( 'qux', $node->rChildren[ 'foo' ]->rChildren );
        self::assertSame( $tn, $node->rChildren[ 'foo' ]->rChildren[ 'qux' ] );
    }


    public function testAddForIntermediateDecoyVariable() : void {
        $node = new TrieNode();
        $tn = $node->add( 'foo$ bar baz', 'BAZ', true );
        self::assertSame( 'BAZ', $tn->xValue );
        self::assertArrayHasKey( 'foo$ bar baz', $node->rChildren );
        self::assertSame( $tn, $node->rChildren[ 'foo$ bar baz' ] );
    }


    public function testAddForIntermediateVariable() : void {
        $node = new TrieNode();
        $tn = $node->add( 'foo${bar}baz', 'BAZ', true );
        self::assertSame( 'BAZ', $tn->xValue );
        self::assertArrayHasKey( 'foo', $node->rChildren );
        self::assertArrayHasKey( '$bar', $node->rChildren[ 'foo' ]->rVariableChildren );
        self::assertSame( $tn, $node->rChildren[ 'foo' ]->rVariableChildren[ '$bar' ]->rChildren[ 'baz' ] );
    }


    public function testAddForLeadingVariable() : void {
        $node = new TrieNode();
        $tn = $node->add( '$foo:bar', 'BAR', true );
        self::assertSame( 'BAR', $tn->xValue );
        self::assertSame( $tn, $node->rVariableChildren[ '$foo' ]->rChildren[ ':bar' ] );
    }


    /** @noinspection SpellCheckingInspection */
    public function testAddForNested() : void {
        $tnRoot = new TrieNode();
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnRoot->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rChildren[ 'bar' ] = $tnBar;
        $tnBar->rChildren[ 'baz' ] = $tnBaz;
        $tnQux = $tnRoot->add( 'foobarbazqux', 'QUX' );
        self::assertSame( 'QUX', $tnQux->xValue );
        self::assertSame( $tnQux, $tnBaz->rChildren[ 'qux' ] );
    }


    public function testAddForNewNodePrefixMatch() : void {
        $node = new TrieNode();
        $node->rChildren[ 'foobar' ] = new TrieNode( 'baz' );
        $tn = $node->add( 'foo', 'qux' );
        self::assertSame( 'qux', $tn->xValue );
        self::assertSame( $tn, $node->rChildren[ 'foo' ] );
        self::assertSame( 'baz', $node->rChildren[ 'foo' ]->rChildren[ 'bar' ]->xValue );
    }


    public function testAddForNoMatch() : void {
        $node = new TrieNode();
        $tn = $node->add( 'foo', 'bar' );
        self::assertSame( 'bar', $node->rChildren[ 'foo' ]->xValue );
        self::assertSame( $tn, $node->rChildren[ 'foo' ] );

        $node = new TrieNode();
        $node2 = new TrieNode( 'baz' );
        $node->add( 'bar', $node2 );
        self::assertArrayHasKey( 'bar', $node->rChildren );
        self::assertSame( $node2, $node->rChildren[ 'bar' ] );
    }


    /** @noinspection SpellCheckingInspection */
    public function testAddForPartiallyNested() : void {
        $tnRoot = new TrieNode();
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnRoot->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rChildren[ 'bar' ] = $tnBar;
        $tnBar->rChildren[ 'baz' ] = $tnBaz;
        $tnQux = $tnRoot->add( 'foobarqux', 'QUX' );
        self::assertSame( 'QUX', $tnQux->xValue );
        self::assertSame( $tnQux, $tnBar->rChildren[ 'qux' ] );
        self::assertSame( 'BAZ', $tnBar->rChildren[ 'baz' ]->xValue );
    }


    public function testAddForSelf() : void {
        $node = new TrieNode();
        $tn = $node->add( '', 'bar' );
        self::assertSame( 'bar', $tn->xValue );
        self::assertSame( $tn, $node );

        $node = new TrieNode( 'foo' );
        self::expectException( InvalidArgumentException::class );
        $node->add( '', 'bar' );
    }


    public function testAddVariableChild() : void {
        $node = new TrieNode();
        $tnFoo = $node->add( '$foo', null, true );
        $tnBar = $tnFoo->add( 'bar', 'BAR', true );
        self::assertSame( 'BAR', $tnBar->xValue );
        self::assertSame( $tnBar, $node->rVariableChildren[ '$foo' ]->rChildren[ 'bar' ] );
    }


    public function testAddVariableChildForDuplicate() : void {
        $node = new TrieNode();
        $tn = $node->addVariableChild( '$foo', '', 'bar', false );
        self::assertSame( 'bar', $tn->xValue );
        self::assertSame( $tn, $node->rVariableChildren[ '$foo' ] );

        self::expectException( InvalidArgumentException::class );
        $node->addVariableChild( '$foo', '', 'qux', false );
    }


    public function testAddVariableChildForMultiple() : void {
        $node = new TrieNode();
        $tn = $node->addVariableChild( '$foo', '$bar', 'baz', false );
        self::assertSame( 'baz', $tn->xValue );
        self::assertSame( $tn, $node->rVariableChildren[ '$foo' ]->rVariableChildren[ '$bar' ] );
    }


    public function testAsNode() : void {
        $tn = TrieNode::asNode( 'foo' );
        self::assertSame( 'foo', $tn->xValue );
        self::assertEmpty( $tn->rChildren );

        $node = new TrieNode( 'foo' );
        $tn = TrieNode::asNode( $node );
        self::assertSame( 'foo', $tn->xValue );
        self::assertSame( $node, $tn );
        self::assertEmpty( $tn->rChildren );
    }


    public function testAsNotNode() : void {
        $node = new TrieNode( 'foo' );
        self::assertSame( 'foo', TrieNode::asNotNode( $node ) );
        self::assertSame( 'foo', TrieNode::asNotNode( 'foo' ) );
    }


    public function testCommonPrefix() : void {
        self::assertSame( 'foo', TrieNode::commonPrefix( 'foo', 'foobar' ) );
        self::assertSame( 'foo', TrieNode::commonPrefix( 'foobar', 'foo' ) );
        self::assertSame( 'foo', TrieNode::commonPrefix( 'foobar', 'fooqux' ) );
        self::assertSame( '', TrieNode::commonPrefix( 'foo', 'bar' ) );
        self::assertSame( '', TrieNode::commonPrefix( '', '' ) );
        self::assertSame( '', TrieNode::commonPrefix( 'foo', '' ) );
        self::assertSame( '', TrieNode::commonPrefix( '', 'foo' ) );
    }


    public function testConstruct() : void {
        $node = new TrieNode();
        self::assertNull( $node->xValue );
        self::assertEmpty( $node->rChildren );

        $node2 = new TrieNode( 'foo' );
        self::assertSame( 'foo', $node2->xValue );
        self::assertEmpty( $node2->rChildren );
    }


    public function testExtractVariableName() : void {
        [ $stVar, $stRest ] = TrieNode::extractVariableName( '$foo/bar' );
        self::assertSame( '$foo', $stVar );
        self::assertSame( '/bar', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '$foo#bar' );
        self::assertSame( '$foo', $stVar );
        self::assertSame( '#bar', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '$foo' );
        self::assertSame( '$foo', $stVar );
        self::assertSame( '', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '${foo}bar' );
        self::assertSame( '$foo', $stVar );
        self::assertSame( 'bar', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '${foo/bar}baz' );
        self::assertSame( '$foo/bar', $stVar );
        self::assertSame( 'baz', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( 'foo${bar}' );
        self::assertNull( $stVar );
        self::assertSame( 'foo${bar}', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( 'foo$bar' );
        self::assertNull( $stVar );
        self::assertSame( 'foo$bar', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '$' );
        self::assertNull( $stVar );
        self::assertSame( '$', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '$ foo' );
        self::assertNull( $stVar );
        self::assertSame( '$ foo', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '${foo bar' );
        self::assertNull( $stVar );
        self::assertSame( '${foo bar', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '${}foo' );
        self::assertNull( $stVar );
        self::assertSame( '${}foo', $stRest );

    }


    /** @noinspection SpellCheckingInspection */
    public function testGet() : void {
        $tnRoot = new TrieNode();
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnRoot->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rChildren[ 'bar' ] = $tnBar;
        $tnBar->rChildren[ 'baz' ] = $tnBaz;

        $stPath = 'foo';
        self::assertSame( $tnFoo, $tnRoot->get( $stPath ) );
        self::assertSame( '', $stPath );

        $stPath = 'foobar';
        self::assertSame( $tnBar, $tnRoot->get( $stPath ) );
        self::assertSame( '', $stPath );

        $stPath = 'foobarbaz';
        self::assertSame( $tnBaz, $tnRoot->get( $stPath ) );
        self::assertSame( '', $stPath );

        $stPath = 'foobarqux';
        self::assertSame( $tnBar, $tnRoot->get( $stPath ) );
        self::assertSame( 'qux', $stPath );
    }


    public function testGetChild() : void {
        $node = new TrieNode();
        $node->rChildren[ 'foo' ] = new TrieNode( 'bar' );

        $stPath = 'foobar';
        $tn = $node->getChild( $stPath );
        self::assertSame( 'bar', $tn->xValue );
        self::assertSame( 'bar', $stPath );

        $stPath = 'fop';
        self::assertNull( $node->getChild( $stPath ) );
    }


    public function testGetWithVariable() : void {
        $tnRoot = new TrieNode();
        $tnFoo = new TrieNode( 'FOO' );
        $tnBar = new TrieNode( 'BAR' );
        $tnBaz = new TrieNode( 'BAZ' );
        $tnRoot->rChildren[ 'foo' ] = $tnFoo;
        $tnFoo->rVariableChildren[ '$bar' ] = $tnBar;
        $tnBar->rChildren[ 'baz' ] = $tnBaz;

        $stPath = 'foo$bar';
        self::assertSame( $tnFoo, $tnRoot->get( $stPath ) );
        self::assertSame( '$bar', $stPath );
    }


    public function testSet() : void {
        $node = new TrieNode();
        $node->rChildren[ 'foo' ] = new TrieNode( 'FOO' );
        $tn = $node->set( 'foo', 'bar' );
        self::assertSame( 'bar', $tn->xValue );
        self::assertSame( $tn, $node->rChildren[ 'foo' ] );
    }


}
