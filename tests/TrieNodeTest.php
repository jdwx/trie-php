<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieNode;
use JDWX\Trie\TrieNodeNavigator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieNode::class )]
final class TrieNodeTest extends TestCase {


    public function testAddConstantForCommonPrefix() : void {
        $node = new TrieNode( null, null );
        $tnBar = $node->rConstants[ 'FooBar' ] = new TrieNode( 'BAR', $node );
        self::assertSame( 'BAR', $tnBar->xValue );
        self::assertSame( $tnBar, $node->rConstants[ 'FooBar' ] );

        $tnQux = $node->addConstant( 'FooQux', 'QUX', false );
        self::assertSame( 'QUX', $node->rConstants[ 'Foo' ]->rConstants[ 'Qux' ]->xValue );
        self::assertSame( $tnBar, $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ] );
        self::assertSame( $tnQux, $node->rConstants[ 'Foo' ]->rConstants[ 'Qux' ] );
    }


    public function testAddConstantForDuplicate() : void {
        $node = new TrieNode( null, null );
        $node->addConstant( 'Foo', 'FOO', false );
        self::assertSame( 'FOO', $node->rConstants[ 'Foo' ]->xValue );

        $node->addConstant( 'Foo', 'BAR', true );
        self::assertSame( 'BAR', $node->rConstants[ 'Foo' ]->xValue );

        $this->expectException( InvalidArgumentException::class );
        $node->addConstant( 'Foo', 'BAZ', false );
    }


    public function testAddConstantForNewKey() : void {
        $node = new TrieNode( null, null );
        $tn = $node->addConstant( 'foo', 'FOO', false );
        self::assertSame( 'FOO', $tn->xValue );
        self::assertSame( $tn, $node->rConstants[ 'foo' ] );
        self::assertSame( $node, $tn->parent );
    }


    public function testAddVariableChild() : void {
        $node = new TrieNode( null, null );
        $tn = $node->addVariable( '$foo', 'FOO', false );
        self::assertSame( 'FOO', $node->rVariables[ '$foo' ]->xValue );
        self::assertSame( $tn, $node->rVariables[ '$foo' ] );

        $tn2 = $node->addVariable( '$foo', 'BAR', true );
        self::assertSame( 'BAR', $node->rVariables[ '$foo' ]->xValue );
        self::assertSame( $tn, $node->rVariables[ '$foo' ] );
        self::assertSame( $tn, $tn2 );

        $this->expectException( InvalidArgumentException::class );
        $node->addVariable( '$foo', 'BAZ', false );
    }


    public function testAsNode() : void {
        $tn = TrieNode::asNode( 'foo' );
        self::assertSame( 'foo', $tn->xValue );
        self::assertEmpty( $tn->rConstants );

        $node = new TrieNode( 'foo', null );
        $tn = TrieNode::asNode( $node );
        self::assertSame( 'foo', $tn->xValue );
        self::assertSame( $node, $tn );
        self::assertEmpty( $tn->rConstants );
    }


    public function testAsNotNode() : void {
        $node = new TrieNode( 'foo', null );
        self::assertSame( 'foo', TrieNode::asNotNode( $node ) );
        self::assertSame( 'foo', TrieNode::asNotNode( 'foo' ) );
    }


    public function testCast() : void {
        $node = new TrieNode( 'foo', null );
        self::assertSame( $node, TrieNode::cast( $node ) );
        $this->expectException( InvalidArgumentException::class );
        TrieNodeNavigator::cast( $node );

    }


    public function testChild() : void {
        $node = new TrieNode( null, null );
        self::assertNull( $node->constant( 'Foo' ) );
        $tnFoo = new TrieNode( 'FOO', null );
        $node->rConstants[ 'Foo' ] = $tnFoo;
        self::assertSame( $tnFoo, $node->constant( 'Foo' ) );
        self::assertNull( $node->constant( 'Bar' ) );
    }


    public function testCommonPrefix() : void {
        self::assertSame( 'Foo', TrieNode::commonPrefix( 'Foo', 'FooBar' ) );
        self::assertSame( 'Foo', TrieNode::commonPrefix( 'Foobar', 'Foo' ) );
        self::assertSame( 'Foo', TrieNode::commonPrefix( 'Foobar', 'FooQux' ) );
        self::assertSame( '', TrieNode::commonPrefix( 'Foo', 'Bar' ) );
        self::assertSame( '', TrieNode::commonPrefix( '', '' ) );
        self::assertSame( '', TrieNode::commonPrefix( 'Foo', '' ) );
        self::assertSame( '', TrieNode::commonPrefix( '', 'Foo' ) );
    }


    public function testConstant() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rConstants[ 'Foo' ] = new TrieNode( 'FOO', null );
        self::assertSame( $tnFoo, $node->constant( 'Foo' ) );
    }


    public function testConstantEx() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rConstants[ 'Foo' ] = new TrieNode( 'FOO', null );
        self::assertSame( $tnFoo, $node->constantEx( 'Foo' ) );
        $this->expectException( InvalidArgumentException::class );
        $node->constantEx( 'Bar' );
    }


    public function testConstants() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rConstants[ 'Foo' ] = new TrieNode( 'FOO', null );
        $tnBar = $node->rConstants[ 'Bar' ] = new TrieNode( 'BAR', null );
        self::assertSame( [ 'Foo' => $tnFoo, 'Bar' => $tnBar ], iterator_to_array( $node->constants() ) );
    }


    public function testConstruct() : void {
        $node = new TrieNode( null, null );
        self::assertNull( $node->xValue );
        self::assertEmpty( $node->rConstants );

        $node2 = new TrieNode( 'foo', null );
        self::assertSame( 'foo', $node2->xValue );
        self::assertEmpty( $node2->rConstants );
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


    public function testExtractVariableNameForCustom() : void {
        TrieNode::$fnExtractVarName = static function ( $stPath ) {
            # Silly example, variable names may contain only digits.
            if ( ! preg_match( '/^(\$\d+)(.*)$/', $stPath, $matches ) ) {
                return [ null, $stPath ];
            }
            return [ $matches[ 1 ], $matches[ 2 ] ];
        };
        [ $stVar, $stRest ] = TrieNode::extractVariableName( '$123Foo' );
        self::assertSame( '$123', $stVar );
        self::assertSame( 'Foo', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( '$123' );
        self::assertSame( '$123', $stVar );
        self::assertSame( '', $stRest );

        [ $stVar, $stRest ] = TrieNode::extractVariableName( 'FooBar' );
        self::assertNull( $stVar );
        self::assertSame( 'FooBar', $stRest );

        TrieNode::$fnExtractVarName = null;
    }


    public function testExtractVariableValue() : void {
        [ $stMatch, $stPath ] = TrieNode::extractVariableValue( '$var', 'FooBarBaz' );
        self::assertSame( 'FooBarBaz', $stMatch );
        self::assertSame( '', $stPath );

        [ $stMatch, $stPath ] = TrieNode::extractVariableValue( '$var', 'FooBar:Baz' );
        self::assertSame( 'FooBar', $stMatch );
        self::assertSame( ':Baz', $stPath );

        [ $stMatch, $stPath ] = TrieNode::extractVariableValue( '$var', 'FooBarBaz Qux' );
        self::assertSame( 'FooBarBaz', $stMatch );
        self::assertSame( ' Qux', $stPath );

        [ $stMatch, $stPath ] = TrieNode::extractVariableValue( '$var', '!FooBarBaz' );
        self::assertNull( $stMatch );
        self::assertSame( '!FooBarBaz', $stPath );
    }


    public function testExtractVariableValueForCustom() : void {
        TrieNode::$fnExtractVarValue = static function ( $stVar, $stPath ) {
            # Silly example, variable values may only contain a lowercase x.
            $uPos = strspn( $stPath, 'x' );
            if ( $uPos === 0 ) {
                return [ null, $stPath ];
            }
            $stMatch = substr( $stPath, 0, $uPos );
            $stPath = substr( $stPath, $uPos );
            return [ $stMatch, $stPath ];
        };
        [ $stMatch, $stPath ] = TrieNode::extractVariableValue( '$var', 'FooBarBaz' );
        self::assertNull( $stMatch );
        self::assertSame( 'FooBarBaz', $stPath );

        [ $stMatch, $stPath ] = TrieNode::extractVariableValue( '$var', 'xxxFoo' );
        self::assertSame( 'xxx', $stMatch );
        self::assertSame( 'Foo', $stPath );

        TrieNode::$fnExtractVarValue = null;
    }


    public function testFindParentKey() : void {
        $node = new TrieNode( null, null );
        self::assertNull( $node->findParentKey() );

        $node2 = new TrieNode( null, null );
        $node->rConstants[ 'foo' ] = $node2;
        $node2->parent = $node;

        self::assertSame( 'foo', $node2->findParentKey() );

        $node3 = new TrieNode( null, null );
        $node->rVariables[ '$bar' ] = $node3;
        $node3->parent = $node;
        self::assertSame( '$bar', $node3->findParentKey() );

        $node = new TrieNode( 'Foo', null );
        $node2 = new TrieNode( 'Bar', $node );
        $this->expectException( LogicException::class );
        $node2->findParentKey();
    }


    public function testFindPath() : void {
        $root = new TrieNode( null, null );
        $tnFoo = new TrieNode( 'foo', null );
        $tnBar = new TrieNode( 'bar', null );
        $tnBaz = new TrieNode( 'baz', null );
        $root->rConstants[ 'Foo' ] = $tnFoo;
        $tnFoo->parent = $root;
        $tnFoo->rConstants[ 'Bar' ] = $tnBar;
        $tnBar->parent = $tnFoo;
        $tnBar->rVariables[ '$Baz' ] = $tnBaz;
        $tnBaz->parent = $tnBar;

        self::assertSame( 'FooBar$Baz', $tnBaz->findPath() );
    }


    public function testGetChild() : void {
        $node = new TrieNode( null, null );
        $node->rConstants[ 'foo' ] = new TrieNode( 'bar', null );

        $stPath = 'foobar';
        $tn = $node->getConstant( $stPath );
        self::assertSame( 'bar', $tn->xValue );
        self::assertSame( 'bar', $stPath );

        $stPath = 'fop';
        self::assertNull( $node->getConstant( $stPath ) );
    }


    public function testGetChildForPrune() : void {
        $tnFoo = new TrieNode( 'foo', null );
        $tnBar = new TrieNode( null, null );
        $tnFoo->rConstants[ 'foo' ] = $tnBar;

        $stPath = 'foobar';
        self::assertNull( $tnFoo->getConstant( $stPath ) );
        self::assertArrayNotHasKey( 'foo', $tnFoo->rConstants );
    }


    public function testIsDead() : void {
        $node = new TrieNode( null, null );
        self::assertTrue( $node->isDead() );

        $node = new TrieNode( 'foo', null );
        self::assertFalse( $node->isDead() );

        $node = new TrieNode( null, null );
        $node->rConstants[ 'foo' ] = new TrieNode( null, null );
        self::assertFalse( $node->isDead() );

        $node = new TrieNode( null, null );
        $node->rVariables[ '$foo' ] = new TrieNode( 'foo', null );
        self::assertFalse( $node->isDead() );
    }


    public function testLinkConstant() : void {
        $node = new TrieNode( null, null );
        $tnFoo = new TrieNode( 'FOO', null );
        $node->linkConstant( 'Foo', $tnFoo );
        self::assertSame( $tnFoo, $node->rConstants[ 'Foo' ] );
        self::assertSame( $node, $tnFoo->parent );

        $tnBar = $node->linkConstant( 'Bar', 'BAR' );
        self::assertSame( 'BAR', $tnBar->xValue );
        self::assertSame( $node, $tnBar->parent );
        self::assertSame( $tnBar, $node->rConstants[ 'Bar' ] );

        $this->expectException( LogicException::class );
        $node->linkConstant( 'Bar', 'BAZ' );
    }


    public function testLinkVariable() : void {
        $node = new TrieNode( null, null );
        $tnFoo = new TrieNode( 'FOO', null );
        $node->linkVariable( '$Foo', $tnFoo );
        self::assertSame( $tnFoo, $node->rVariables[ '$Foo' ] );
        self::assertSame( $node, $tnFoo->parent );

        $tnBar = $node->linkVariable( '$Bar', 'BAR' );
        self::assertSame( 'BAR', $tnBar->xValue );
        self::assertSame( $node, $tnBar->parent );
        self::assertSame( $tnBar, $node->rVariables[ '$Bar' ] );

        $this->expectException( LogicException::class );
        $node->linkVariable( '$Bar', 'BAZ' );
    }


    public function testMatchConstantPrefix() : void {
        $node = new TrieNode( null, null );
        $node->rConstants[ 'Foo' ] = new TrieNode( null, null );
        $node->rConstants[ 'Baz' ] = new TrieNode( null, null );

        [ $stPrefix, $stKey, $stRest ] = $node->matchConstantPrefix( 'FooBar' );
        self::assertSame( 'Foo', $stPrefix );
        self::assertSame( 'Foo', $stKey );
        self::assertSame( 'Bar', $stRest );

        $node = new TrieNode( null, null );
        $node->rConstants[ 'FooBar' ] = new TrieNode( null, null );

        [ $stPrefix, $stKey, $stRest ] = $node->matchConstantPrefix( 'FooQux' );
        self::assertSame( 'Foo', $stPrefix );
        self::assertSame( 'FooBar', $stKey );
        self::assertSame( 'Qux', $stRest );

        [ $stPrefix, $stKey, $stRest ] = $node->matchConstantPrefix( 'Foo' );
        self::assertSame( 'Foo', $stPrefix );
        self::assertSame( 'FooBar', $stKey );
        self::assertSame( '', $stRest );

        [ $stPrefix, $stKey, $stRest ] = $node->matchConstantPrefix( 'Bar' );
        self::assertNull( $stPrefix );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

    }


    public function testMatchVariablePrefix() : void {
        $node = new TrieNode( null, null );
        $node->rVariables[ '$Foo' ] = new TrieNode( null, null );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '${Foo}Bar' );
        self::assertSame( '$Foo', $stKey );
        self::assertSame( 'Bar', $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '${Qux}Corge' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$Foo' );
        self::assertSame( '$Foo', $stKey );
        self::assertSame( '', $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$Bar' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$FooBar' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

        $node = new TrieNode( null, null );
        $node->rVariables[ '$FooBar' ] = new TrieNode( null, null );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$FooQux' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$FooBar' );
        self::assertSame( '$FooBar', $stKey );
        self::assertSame( '', $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$Bar' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$FooBarQux' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( '$Foo' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );

        [ $stKey, $stRest ] = $node->matchVariablePrefix( 'FooBar' );
        self::assertNull( $stKey );
        self::assertNull( $stRest );


    }


    public function testNodePruneForVariable() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rVariables[ '$Foo' ] = new TrieNode( 'FOO', null );
        $tnFoo->parent = $node;
        $tnBar = $tnFoo->rConstants[ 'Bar' ] = new TrieNode( 'BAR', null );
        $tnBar->parent = $tnFoo;
        $tnBaz = $tnFoo->rVariables[ '$Baz' ] = new TrieNode( 'BAZ', null );
        $tnBaz->parent = $tnFoo;
        $tnFoo->prune();
        self::assertNull( $tnFoo->parent );
        self::assertArrayNotHasKey( '$Foo', $node->rVariables );
        self::assertSame( $tnBar, $tnFoo->rConstants[ 'Bar' ] );
        self::assertSame( $tnBaz, $tnFoo->rVariables[ '$Baz' ] );
    }


    public function testPruneForConstant() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rConstants[ 'Foo' ] = new TrieNode( 'FOO', null );
        $tnFoo->parent = $node;
        $tnBar = $tnFoo->rConstants[ 'Bar' ] = new TrieNode( 'BAR', null );
        $tnBar->parent = $tnFoo;
        $tnBaz = $tnFoo->rVariables[ '$Baz' ] = new TrieNode( 'BAZ', null );
        $tnBaz->parent = $tnFoo;
        $tnFoo->prune();
        self::assertNull( $tnFoo->parent );
        self::assertArrayNotHasKey( 'Foo', $node->rConstants );
        self::assertSame( $tnBar, $tnFoo->rConstants[ 'Bar' ] );
        self::assertSame( $tnBaz, $tnFoo->rVariables[ '$Baz' ] );

        $node->prune();
        self::assertNull( $node->parent );
    }


    public function testSetValue() : void {
        $node = new TrieNode( null, null );
        self::assertNull( $node->xValue );
        $node->setValue( 'foo', false );
        self::assertSame( 'foo', $node->xValue );

        $node->setValue( 'bar', true );
        self::assertSame( 'bar', $node->xValue );

        $this->expectException( InvalidArgumentException::class );
        $node->setValue( 'baz', false );
    }


    public function testSplitForCommonPrefix() : void {
        $node = new TrieNode( null, null );
        $tnBar = $node->rConstants[ 'FooBar' ] = new TrieNode( 'BAR', null );
        $tnBar->rConstants[ 'Baz' ] = new TrieNode( 'BAZ', null );
        $node->splitConstant( 'Foo', 'FooBar', 'Qux', 'QUX' );
        self::assertSame( 'BAR', $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame( 'QUX', $node->rConstants[ 'Foo' ]->rConstants[ 'Qux' ]->xValue );
        self::assertSame( 'BAZ', $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->xValue );
        self::assertSame( $node, $node->rConstants[ 'Foo' ]->parent );
        self::assertSame(
            $node->rConstants[ 'Foo' ],
            $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->parent
        );
        self::assertSame(
            $node->rConstants[ 'Foo' ],
            $node->rConstants[ 'Foo' ]->rConstants[ 'Qux' ]->parent
        );
    }


    public function testSplitForExistingIsSubstring() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rConstants[ 'Foo' ] = new TrieNode( 'FOO', null );
        $tnFoo->parent = $node;
        $tnBar = $tnFoo->rConstants[ 'Bar' ] = new TrieNode( 'BAR', null );
        $tnBar->parent = $tnFoo;
        $node->splitConstant( 'Foo', 'Foo', 'Qux', 'QUX' );
        self::assertSame( 'FOO', $node->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame( 'QUX', $node->rConstants[ 'Foo' ]->rConstants[ 'Qux' ]->xValue );
        self::assertSame( $node, $node->rConstants[ 'Foo' ]->parent );
        self::assertSame(
            $node->rConstants[ 'Foo' ],
            $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->parent
        );
        self::assertSame(
            $node->rConstants[ 'Foo' ],
            $node->rConstants[ 'Foo' ]->rConstants[ 'Qux' ]->parent
        );
    }


    public function testSplitForNewIsSubstring() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rConstants[ 'FooBar' ] = new TrieNode( 'BAR', null );
        $tnFoo->parent = $node;
        $tnBaz = $tnFoo->rConstants[ 'Baz' ] = new TrieNode( 'BAZ', null );
        $tnBaz->parent = $tnFoo;
        $node->splitConstant( 'Foo', 'FooBar', '', 'FOO' );
        self::assertSame( 'FOO', $node->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame( 'BAZ', $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->xValue );
        self::assertSame( $node, $node->rConstants[ 'Foo' ]->parent );
        self::assertSame(
            $node->rConstants[ 'Foo' ],
            $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->parent
        );
        self::assertSame(
            $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ],
            $node->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->parent
        );
    }


    public function testUnsetValue() : void {
        $node = new TrieNode( null, null );
        $node->unsetValue();
        self::assertNull( $node->xValue );

        $node = new TrieNode( 'foo', null );
        $node->unsetValue();
        self::assertNull( $node->xValue );
    }


    public function testVariable() : void {
        $node = new TrieNode( null, null );
        self::assertNull( $node->variable( '$Foo' ) );
        $tnFoo = new TrieNode( 'FOO', null );
        $node->rVariables[ '$Foo' ] = $tnFoo;
        self::assertSame( $tnFoo, $node->variable( '$Foo' ) );
        self::assertNull( $node->variable( '$Bar' ) );
    }


    public function testVariableEx() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rVariables[ '$Foo' ] = new TrieNode( 'FOO', null );
        self::assertSame( $tnFoo, $node->variableEx( '$Foo' ) );
        $this->expectException( InvalidArgumentException::class );
        $node->variableEx( '$Bar' );
    }


    public function testVariables() : void {
        $node = new TrieNode( null, null );
        $tnFoo = $node->rVariables[ '$Foo' ] = new TrieNode( 'FOO', null );
        $tnBar = $node->rVariables[ '$Bar' ] = new TrieNode( 'BAR', null );
        self::assertSame( [ '$Foo' => $tnFoo, '$Bar' => $tnBar ], iterator_to_array( $node->variables() ) );
    }


}
