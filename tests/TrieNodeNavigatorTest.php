<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieMatch;
use JDWX\Trie\TrieNodeNavigator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieNodeNavigator::class )]
final class TrieNodeNavigatorTest extends TestCase {


    public function testAddForConstants() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnRoot->add( 'Foo', 'FOO', false, false );
        $tnRoot->add( 'FooBar', 'BAR', false, false );
        $tnRoot->add( 'FooBarBaz', 'BAZ', false, false );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame( 'BAZ', $tnRoot->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->xValue );
    }


    public function testAddForNoVariables() : void {
        $root = new TrieNodeNavigator( null, null );
        $root->add( 'Foo', 'FOO', false, false );
        $root->add( 'Foo Bar', 'BAR', false, false );
        $root->add( 'Foo Bar:Baz', 'BAZ', false, false );
        $root->add( 'Foo $Bar Qux', 'QUX', false, false );

        self::assertSame( 'FOO', $root->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $root->rConstants[ 'Foo' ]->rConstants[ ' ' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame(
            'BAZ',
            $root->rConstants[ 'Foo' ]->rConstants[ ' ' ]->rConstants[ 'Bar' ]->rConstants[ ':Baz' ]->xValue
        );
        self::assertSame(
            'QUX',
            $root->rConstants[ 'Foo' ]->rConstants[ ' ' ]->rConstants[ '$Bar Qux' ]->xValue
        );

    }


    public function testAddForVariable() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnRoot->add( 'Foo', 'FOO', true, false );
        $tnRoot->add( 'Foo$Bar', 'BAR', true, false );
        $tnRoot->add( 'Foo${Bar}Baz', 'BAZ', true, false );
        $tnRoot->add( 'Foo${Qux}Quux', 'QUUX', true, false );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->xValue );
        self::assertSame(
            'BAZ',
            $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ]->xValue
        );
        self::assertNull( $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Qux' ]->xValue );
        self::assertSame(
            'QUUX',
            $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Qux' ]->rConstants[ 'Quux' ]->xValue
        );
    }


    public function testFindMatchesAfterVariable() : void {
        $tn = new TrieNodeNavigator( null, null );
        $tn->addConstant( 'Bar', 'FOO', false );
        self::assertSame(
            [
                'FooBar' => '',
                'Foo' => 'Bar',
            ],
            iterator_to_array( $tn->findMatchesAfterVariable( 'FooBar' ) )
        );

        self::assertSame(
            [
                'FooBarQux' => '',
                'Foo' => 'BarQux',
            ],
            iterator_to_array( $tn->findMatchesAfterVariable( 'FooBarQux' ) )
        );

        self::assertSame(
            [
                'FooBarBarQux' => '',
                'Foo' => 'BarBarQux',
                'FooBar' => 'BarQux',
            ],
            iterator_to_array( $tn->findMatchesAfterVariable( 'FooBarBarQux' ) )
        );

        $tn->addVariable( '$Bar', 'BAR', false );
        self::expectException( LogicException::class );
        iterator_to_array( $tn->findMatchesAfterVariable( 'FooBar' ), false );
    }


    public function testGet() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnRoot->add( 'Foo', 'FOO', true, false );
        $tnRoot->add( 'Foo#$Bar', 'BAR', true, false );
        $tnRoot->add( 'Foo#$Bar#Baz', 'BAZ', true, false );
        $tnRoot->add( 'Foo#$Bar#Baz#$Qux', 'QUX', true, false );
        $tnRoot->add( 'Foo#$Bar#Baz#$Qux#Quux', 'QUUX', true, false );

        $r = [];
        self::assertSame(
            'FOO',
            $tnRoot->get( 'Foo', $r, true )
        );
        self::assertSame(
            'BAR',
            $tnRoot->get( 'Foo#Corge', $r, true )
        );
        self::assertSame(
            'BAZ',
            $tnRoot->get( 'Foo#Corge#Baz', $r, true )
        );
        self::assertSame(
            'QUX',
            $tnRoot->get( 'Foo#Corge#Baz#Grault', $r, true )
        );
        self::assertSame(
            'QUUX',
            $tnRoot->get( 'Foo#Corge#Baz#Grault#Quux', $r, true )
        );

        self::assertNull( $tnRoot->get( 'Oof', $r, true ) );

    }


    public function testGetForAmbiguousIntermediateVariable() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnBaz = $tnFoo->addVariable( '$Baz', 'BAZ', false );
        $tnBar->addConstant( ' Qux', 'QUX', false );
        $tnBaz->addConstant( ' Qux', 'QUUX', false );

        $r = [];
        self::expectException( RuntimeException::class );
        $root->get( 'FooCorge Qux', $r, true );
    }


    public function testGetForAmbiguousTerminalVariable() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnFoo->addVariable( '$Baz', 'BAZ', false );

        $r = [];
        self::expectException( RuntimeException::class );
        $root->get( 'FooQux', $r, true );
    }


    public function testGetForInvalidVariableValue() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnBar->addVariable( '$Baz', 'BAZ', false );
        $r = [];
        self::expectException( LogicException::class );
        $root->get( 'Foo QuxBaz', $r, true );
    }


    public function testGetForMixedVariableAndConstant() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addConstant( 'Bar', 'BAR', false );
        $tnBaz = $tnFoo->addVariable( '$Baz', 'BAZ', false );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAR', $root->get( 'FooBar', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAZ', $root->get( 'FooQux', $r, true ) );
        self::assertCount( 1, $r );
        self::assertArrayHasKey( '$Baz', $r );
        self::assertContains( 'Qux', $r );

        $tnBar->addConstant( ' Qux', 'QUX', false );
        $tnBaz->addConstant( ' Quux', 'QUUX', false );

        self::assertSame( 'QUX', $root->get( 'FooBar Qux', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'QUUX', $root->get( 'FooCorge Quux', $r, true ) );
        self::assertCount( 1, $r );
    }


    public function testGetForNoMatchAfterVariable() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnBar->addConstant( ' Qux', 'QUX', false );

        $r = [];
        self::assertSame( 'BAR', $root->get( 'FooQux Quux', $r, true ) );

        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', null, false );
        $tnBar->addConstant( ' Qux', 'QUX', false );

        $r = [];
        self::assertNull( $root->get( 'FooQux Quux', $r, true ) );
    }


    public function testGetForPastEnd() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addConstant( 'Bar', 'BAR', false );
        $tnBar->addConstant( 'Baz', 'BAZ', false );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, false ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAR', $root->get( 'FooBar', $r, false ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAZ', $root->get( 'FooBarBaz', $r, false ) );
        self::assertEmpty( $r );
        self::assertNull( $root->get( 'FooBarBazQux', $r, false ) );
        self::assertEmpty( $r );

        $st = '';
        self::assertSame(
            'BAZ',
            $root->get( 'FooBarBazQux', $r, true, $st )
        );
        self::assertSame( 'Qux', $st );
    }


    /**
     * @return void
     */
    public function testGetForUnambiguousTerminalVariable() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBaz = $tnFoo->linkVariable( '$Baz', 'BAR' );
        $tnBar->linkConstant( ' Qux', 'QUX' );
        $tnBaz->linkConstant( ' Quux', 'QUUX' );

        $r = [];
        self::assertSame( 'QUX', $root->get( 'FooCorge Qux', $r, true ) );
        self::assertCount( 1, $r );
        self::assertSame( 'Corge', $r[ '$Bar' ] );
    }


    public function testGetWithVariables() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnBar->addConstant( 'Baz', 'BAZ', false );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, true ) );
        self::assertEmpty( $r );

        $r = [];
        self::assertSame( 'BAR', $root->get( 'FooQux', $r, true ) );
        self::assertCount( 1, $r );
        self::assertSame( 'Qux', $r[ '$Bar' ] );

        $r = [];
        self::assertSame( 'BAZ', $root->get( 'FooQuxBaz', $r, true ) );
        self::assertCount( 1, $r );
        self::assertSame( 'Qux', $r[ '$Bar' ] );
    }


    public function testHas() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );

        self::assertTrue( $tnRoot->has( 'Foo', true, false, false ) );
        self::assertTrue( $tnRoot->has( 'Foo$Bar', true, false, false ) );
        self::assertFalse( $tnRoot->has( 'Foo$Bar', false, false, false ) );
        self::assertTrue( $tnRoot->has( 'Foo${Bar}Baz', true, false, false ) );
        self::assertFalse( $tnRoot->has( 'Foo$Qux', true, false, false ) );
        self::assertTrue( $tnRoot->has( 'FooQuux', true, true, false ) );
    }


    public function testHasForNothing() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnRoot->linkConstant( 'Foo', 'FOO' );
        self::assertFalse( $tnRoot->has( 'Bar', true, false, false ) );
        self::assertFalse( $tnRoot->has( 'Bar', false, false, false ) );
        self::assertFalse( $tnRoot->has( 'Bar', true, false, true ) );
    }


    public function testHasWithVariables() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnBar->addConstant( ' Baz', 'BAZ', false );
        $tnBar->addConstant( ' Qux', 'QUX', false );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, true ) );
        self::assertSame( 'BAR', $root->get( 'FooQuux', $r, true ) );
        self::assertSame( 'BAZ', $root->get( 'FooQuux Baz', $r, true ) );
        self::assertSame( 'QUX', $root->get( 'FooQuux Qux', $r, true ) );

        self::assertTrue( $root->has( 'Foo${Bar} Baz', true,
            false, false ) );
        self::assertTrue( $root->has( 'Foo${Bar} Qux', true,
            false, false ) );
        self::assertFalse( $root->has( 'Foo${Bar} Quux', true,
            false, false ) );
        self::assertFalse( $root->has( 'FooQux', true,
            false, false ) );

        self::assertTrue( $root->has( 'Foo${Bar} Baz', true,
            true, false ) );
        self::assertTrue( $root->has( 'Foo${Bar} Qux', true,
            true, false ) );

        # This one matches Foo = "Foo", $Bar = "${Bar} Quux"
        self::assertTrue( $root->has( 'Foo${Bar} Quux', true,
            true, false ) );

        self::assertTrue( $root->has( 'FooQux', true, true, false ) );

        self::assertFalse( $root->has( 'Foo${Bar} BazQux', true,
            false, false ) );
        self::assertTrue( $root->has( 'Foo${Bar} BazQux', true,
            false, true ) );

    }


    /** @noinspection SpellCheckingInspection */
    public function testListOffsetPairs() : void {
        $r = TrieNodeNavigator::listOffsetPairs( 'Foo', 'Bar' );
        self::assertEmpty( iterator_to_array( $r ) );

        $r = iterator_to_array( TrieNodeNavigator::listOffsetPairs( 'Foo', 'Foo' ) );
        self::assertCount( 0, $r );

        $r = iterator_to_array( TrieNodeNavigator::listOffsetPairs( 'FooBar', 'Foo' ) );
        self::assertCount( 0, $r );

        $r = iterator_to_array( TrieNodeNavigator::listOffsetPairs( 'FooBar', 'Bar' ) );
        self::assertCount( 1, $r );
        self::assertSame( [ 'Foo' => 'Bar' ], $r );

        $r = iterator_to_array( TrieNodeNavigator::listOffsetPairs( 'FooBarBar', 'Bar' ) );
        self::assertCount( 2, $r );
        self::assertSame( 'BarBar', $r[ 'Foo' ] );
        self::assertSame( 'Bar', $r[ 'FooBar' ] );

        $r = iterator_to_array( TrieNodeNavigator::listOffsetPairs( 'baaaaaab', 'aaa' ) );
        self::assertCount( 4, $r );
        self::assertSame( 'aaaaaab', $r[ 'b' ] );
        self::assertSame( 'aaaaab', $r[ 'ba' ] );
        self::assertSame( 'aaaab', $r[ 'baa' ] );
        self::assertSame( 'aaab', $r[ 'baaa' ] );

        $r = iterator_to_array( TrieNodeNavigator::listOffsetPairs( 'cababababac', 'ababa' ) );
        self::assertCount( 3, $r );
        self::assertSame( 'ababababac', $r[ 'c' ] );
        self::assertSame( 'abababac', $r[ 'cab' ] );
        self::assertSame( 'ababac', $r[ 'cabab' ] );
    }


    /** @noinspection SpellCheckingInspection */
    public function testListStringOffsets() : void {
        $r = iterator_to_array( TrieNodeNavigator::listStringOffsets( 'Foo', 'Baz' ) );
        self::assertEmpty( $r );

        $r = iterator_to_array( TrieNodeNavigator::listStringOffsets( 'Foo', 'Foo' ) );
        self::assertSame( [ 0 ], $r );

        $r = iterator_to_array( TrieNodeNavigator::listStringOffsets( 'FooBar', 'Foo' ) );
        self::assertSame( [ 0 ], $r );

        $r = iterator_to_array( TrieNodeNavigator::listStringOffsets( 'FooBaz', 'Baz' ) );
        self::assertSame( [ 3 ], $r );

        $r = iterator_to_array( TrieNodeNavigator::listStringOffsets( 'FooBazBaz', 'Baz' ) );
        self::assertSame( [ 3, 6 ], $r );

        $r = iterator_to_array( TrieNodeNavigator::listStringOffsets( 'baaaaaab', 'aaa' ) );
        self::assertSame( [ 1, 2, 3, 4 ], $r );

        $r = iterator_to_array( TrieNodeNavigator::listStringOffsets( 'cababababac', 'ababa' ) );
        self::assertSame( [ 1, 3, 5 ], $r );
    }


    public function testMatchForConstantsOnly() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkConstant( 'Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );

        $r = self::i2a( $tnRoot->match( 'Foo', false, false, [] ) );
        self::assertCount( 1, $r );

        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        $r = self::i2a( $tnRoot->match( 'Foo', true, false, [] ) );
        self::assertCount( 1, $r );

        $r = self::i2a( $tnRoot->match( 'FooBar', false, true, [] ) );
        self::assertCount( 2, $r );

        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( 'Bar', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        $tm = $r[ 1 ];
        self::assertSame( 'BAR', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', 'Bar' => 'Bar' ], $tm->rMatches );

        $r = self::i2a( $tnRoot->match( 'FooBarBaz', true, true, [] ) );
        self::assertCount( 3, $r );
        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( 'BarBaz', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        $tm = $r[ 1 ];
        self::assertSame( 'BAR', $tm->tn->xValue );
        self::assertSame( 'Baz', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', 'Bar' => 'Bar' ], $tm->rMatches );

        $tm = $r[ 2 ];
        self::assertSame( 'BAZ', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', 'Bar' => 'Bar', 'Baz' => 'Baz' ], $tm->rMatches );

    }


    public function testMatchForVariableNoSubst() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnBar->addConstant( 'Baz', 'BAZ', false );

        $r = self::i2a( $root->match( 'Foo', true, false, [] ) );
        self::assertCount( 1, $r );

        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        # This won't work because expansion is disabled.
        $r = self::i2a( $root->match( 'FooQux', true, false, [] ) );
        self::assertCount( 1, $r );
        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( 'Qux', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        $r = self::i2a( $root->match( 'Foo$Bar', true, false, [] ) );
        self::assertCount( 2, $r );
        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( '$Bar', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        $tm = $r[ 1 ];
        self::assertSame( 'BAR', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', '$Bar' => '$Bar' ], $tm->rMatches );

    }


    public function testMatchForVariableValue() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR', false );
        $tnBar->addConstant( 'Baz', 'BAZ', false );

        $r = self::i2a( $root->match( 'FooQux', true, true, [] ) );
        self::assertCount( 2, $r );
        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( 'Qux', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        $tm = $r[ 1 ];
        self::assertSame( 'BAR', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', '$Bar' => 'Qux' ], $tm->rMatches );

        $r = self::i2a(
            $root->match( 'FooQuxBaz', true, true, [] ) );
        self::assertCount( 4, $r );
        $tm = $r[ 0 ];
        self::assertSame( 'FOO', $tm->tn->xValue );
        self::assertSame( 'QuxBaz', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo' ], $tm->rMatches );

        $tm = $r[ 1 ];
        self::assertSame( 'BAR', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', '$Bar' => 'QuxBaz' ], $tm->rMatches );

        $tm = $r[ 2 ];
        self::assertSame( 'BAR', $tm->tn->xValue );
        self::assertSame( 'Baz', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', '$Bar' => 'Qux' ], $tm->rMatches );

        $tm = $r[ 3 ];
        self::assertSame( 'BAZ', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', '$Bar' => 'Qux', 'Baz' => 'Baz' ], $tm->rMatches );

    }


    public function testMatchOne() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );

        $tm = $tnRoot->matchOne( 'Foo', true, false );
        self::assertSame( 'FOO', $tm->tn->xValue );

        $tm = $tnRoot->matchOne( 'Foo$Bar', true, false );
        self::assertSame( 'BAR', $tm->tn->xValue );

        $tm = $tnRoot->matchOne( 'Foo${Bar}Baz', true, false );
        self::assertSame( 'BAZ', $tm->tn->xValue );

        $tm = $tnRoot->matchOne( 'FooQuxBaz', true, true );
        self::assertSame( 'BAZ', $tm->tn->xValue );
        self::assertSame( '', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', '$Bar' => 'Qux', 'Baz' => 'Baz' ], $tm->rMatches );

        $tm = $tnRoot->matchOne( 'FooQuxBazQuux', true, true );
        self::assertSame( 'BAZ', $tm->tn->xValue );
        self::assertSame( 'Quux', $tm->stRest );
        self::assertSame( [ 'Foo' => 'Foo', '$Bar' => 'Qux', 'Baz' => 'Baz' ], $tm->rMatches );
    }


    public function testMatchOneForAmbiguous() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBaz = $tnFoo->linkVariable( '$Baz', 'BAZ' );
        $tnBar->linkConstant( 'Qux', 'QUX' );
        $tnBaz->linkConstant( 'Qux', 'QUUX' );

        self::expectException( RuntimeException::class );
        $tnRoot->matchOne( 'FooQuuxQux', true, true );
    }


    public function testMatchOneForNoMatch() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnRoot->linkConstant( 'Foo', 'FOO' );

        self::assertNull( $tnRoot->matchOne( 'Bar', true, false ) );
    }


    public function testSetForExisting() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBaz = $tnBar->linkConstant( 'Baz', 'BAZ' );

        $tnRoot->set( 'Foo', 'OOF', true, true );
        self::assertSame( $tnFoo, $tnRoot->rConstants[ 'Foo' ] );
        self::assertSame( 'OOF', $tnRoot->rConstants[ 'Foo' ]->xValue );

        $tnRoot->set( 'Foo$Bar', 'RAB', true, true );
        self::assertSame( $tnBar, $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ] );
        self::assertSame( 'RAB', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->xValue );

        $tnRoot->set( 'Foo${Bar}Baz', 'ZAB', true, true );
        self::assertSame( $tnBaz, $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ] );
        self::assertSame( 'ZAB', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ]->xValue );

        self::expectException( InvalidArgumentException::class );
        $tnRoot->set( 'Foo${Bar}Baz', 'QUX', true, false );
    }


    public function testSetForNew() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->set( 'Foo', 'FOO', true, true );

        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( $tnFoo, $tnRoot->rConstants[ 'Foo' ] );

        $tnBar = $tnRoot->set( 'Foo$Bar', 'BAR', true, true );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->xValue );
        self::assertSame( $tnBar, $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ] );

        $tnBaz = $tnRoot->set( 'Foo${Bar}Baz', 'BAZ', true, true );
        self::assertSame( 'BAZ', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ]->xValue );
        self::assertSame( $tnBaz, $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ] );

        $tnQux = $tnRoot->set( 'Foo${Bar}$Qux', 'QUX', true, true );
        self::assertSame( 'QUX', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rVariables[ '$Qux' ]->xValue );
        self::assertSame( $tnQux, $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rVariables[ '$Qux' ] );

        $tnQuux = $tnRoot->set( '$Quux', 'QUUX', true, true );
        self::assertSame( 'QUUX', $tnRoot->rVariables[ '$Quux' ]->xValue );
        self::assertSame( $tnQuux, $tnRoot->rVariables[ '$Quux' ] );
    }


    public function testUnsetForConstants() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->addConstant( 'Foo', 'FOO', false );
        $tnBar = $tnFoo->addConstant( 'Bar', 'BAR', false );
        $tnBar->addConstant( 'Baz', 'BAZ', false );
        $tnBar->addVariable( '$Qux', 'QUX', false );

        $root->unset( 'FooBar', false, false );
        self::assertNull( $root->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame( 'BAZ', $root->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->xValue );
        self::assertSame( 'QUX', $root->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rVariables[ '$Qux' ]->xValue );
    }


    public function testUnsetForNotSet() : void {
        $tnRoot = new TrieNodeNavigator( 'BAZ', null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnFoo->linkConstant( 'Bar', 'BAR' );

        self::assertSame( 'BAZ', $tnRoot->xValue );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );

        $tnRoot->unset( 'Baz', true, false );

        self::assertSame( 'BAZ', $tnRoot->xValue );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );

        $tnRoot->unset( '', true, false );

        self::assertNull( $tnRoot->xValue );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
    }


    public function testUnsetForPrune() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkConstant( 'Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );

        $tnRoot->unset( 'FooBar', true, true );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertArrayNotHasKey( 'Bar', $tnRoot->rConstants[ 'Foo' ]->rConstants );
    }


    public function testUnsetForPrune2() : void {
        $root = new TrieNodeNavigator( null, null );
        $tnFoo = $root->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkConstant( 'Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );
        $tnBar->linkVariable( '$Qux', 'QUX' );
        $tnFoo->linkConstant( 'Quux', 'QUUX' );

        $root->unset( 'FooBar', true, true );
        self::assertArrayNotHasKey( 'Bar', $root->rConstants[ 'Foo' ]->rConstants );
    }


    public function testUnsetForVariables() : void {
        $tnRoot = new TrieNodeNavigator( null, null );
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );
        $tnBar->linkConstant( 'Qux', 'QUX' );

        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->xValue );
        self::assertSame( 'BAZ', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ]->xValue );

        $tnRoot->unset( 'Foo$Bar', true, false );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAZ', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Baz' ]->xValue );
        self::assertNull( $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->xValue );

        $tnRoot->unset( 'Foo${Bar}Baz', true, false );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertArrayNotHasKey( 'Baz', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants );
        self::assertSame( 'QUX', $tnRoot->rConstants[ 'Foo' ]->rVariables[ '$Bar' ]->rConstants[ 'Qux' ]->xValue );

        $tnRoot->unset( 'Foo${Bar}Qux', true, false );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertArrayNotHasKey( '$Bar', $tnRoot->rConstants[ 'Foo' ]->rVariables );
    }


    /**
     * @param iterable<TrieMatch> $i_r
     * @return list<TrieMatch>
     */
    private function i2a( iterable $i_r ) : array {
        return iterator_to_array( $i_r, false );
    }


}