<?php


declare( strict_types = 1 );


use JDWX\Trie\TrieNodeNavigator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TrieNodeNavigator::class )]
final class TrieNodeNavigatorTest extends TestCase {


    public function testAddForConstants() : void {
        $tnRoot = new TrieNodeNavigator();
        $tnRoot->add( 'Foo', 'FOO' );
        $tnRoot->add( 'FooBar', 'BAR' );
        $tnRoot->add( 'FooBarBaz', 'BAZ' );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertSame( 'BAR', $tnRoot->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame( 'BAZ', $tnRoot->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->xValue );
    }


    public function testAddForNoVariables() : void {
        $root = new TrieNodeNavigator();
        $root->add( 'Foo', 'FOO' );
        $root->add( 'Foo Bar', 'BAR' );
        $root->add( 'Foo Bar:Baz', 'BAZ' );
        $root->add( 'Foo $Bar Qux', 'QUX' );

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
        $tnRoot = new TrieNodeNavigator();
        $tnRoot->add( 'Foo', 'FOO', true );
        $tnRoot->add( 'Foo$Bar', 'BAR', true );
        $tnRoot->add( 'Foo${Bar}Baz', 'BAZ', true );
        $tnRoot->add( 'Foo${Qux}Quux', 'QUUX', true );
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


    public function testGet() : void {
        $tnRoot = new TrieNodeNavigator();
        $tnRoot->add( 'Foo', 'FOO', true );
        $tnRoot->add( 'Foo#$Bar', 'BAR', true );
        $tnRoot->add( 'Foo#$Bar#Baz', 'BAZ', true );
        $tnRoot->add( 'Foo#$Bar#Baz#$Qux', 'QUX', true );
        $tnRoot->add( 'Foo#$Bar#Baz#$Qux#Quux', 'QUUX', true );

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
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR' );
        $tnBaz = $tnFoo->addVariable( '$Baz', 'BAZ' );
        $tnBar->addConstant( ' Qux', 'QUX' );
        $tnBaz->addConstant( ' Qux', 'QUUX' );

        $r = [];
        self::expectException( RuntimeException::class );
        $root->get( 'FooCorge Qux', $r, true );
    }


    public function testGetForAmbiguousTerminalVariable() : void {
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnFoo->addVariable( '$Bar', 'BAR' );
        $tnFoo->addVariable( '$Baz', 'BAZ' );

        $r = [];
        self::expectException( RuntimeException::class );
        $root->get( 'FooQux', $r, true );
    }


    public function testGetForInvalidVariableValue() : void {
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR' );
        $tnBar->addVariable( '$Baz', 'BAZ' );
        $r = [];
        self::assertNull( $root->get( 'Foo QuxBaz', $r ) );
    }


    public function testGetForMixedVariableAndConstant() : void {
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addConstant( 'Bar', 'BAR' );
        $tnBaz = $tnFoo->addVariable( '$Baz', 'BAZ' );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAR', $root->get( 'FooBar', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAZ', $root->get( 'FooQux', $r, true ) );
        self::assertCount( 1, $r );
        self::assertArrayHasKey( '$Baz', $r );
        self::assertContains( 'Qux', $r );

        $tnBar->addConstant( ' Qux', 'QUX' );
        $tnBaz->addConstant( ' Quux', 'QUUX' );

        self::assertSame( 'QUX', $root->get( 'FooBar Qux', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'QUUX', $root->get( 'FooCorge Quux', $r, true ) );
        self::assertCount( 1, $r );
    }


    public function testGetForNoMatchAfterVariable() : void {
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR' );
        $tnBar->addConstant( ' Qux', 'QUX' );

        $r = [];
        self::assertNull( $root->get( 'FooQux Quux', $r ) );
    }


    public function testGetForPastEnd() : void {
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addConstant( 'Bar', 'BAR' );
        $tnBar->addConstant( 'Baz', 'BAZ' );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAR', $root->get( 'FooBar', $r, true ) );
        self::assertEmpty( $r );
        self::assertSame( 'BAZ', $root->get( 'FooBarBaz', $r ) );
        self::assertEmpty( $r );
        self::assertNull( $root->get( 'FooBarBazQux', $r ) );
        self::assertEmpty( $r );
    }


    /**
     * @return void
     */
    public function testGetForUnambiguousTerminalVariable() : void {
        $root = new TrieNodeNavigator();
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
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR' );
        $tnBar->addConstant( ' Baz', 'BAZ' );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, true ) );
        self::assertEmpty( $r );

        $r = [];
        self::assertSame( 'BAR', $root->get( 'FooQux', $r, true ) );
        self::assertCount( 1, $r );
        self::assertSame( 'Qux', $r[ '$Bar' ] );

        $r = [];
        self::assertSame( 'BAZ', $root->get( 'FooQux Baz', $r, true ) );
        self::assertCount( 1, $r );
        self::assertSame( 'Qux', $r[ '$Bar' ] );
    }


    public function testHas() : void {
        $tnRoot = new TrieNodeNavigator();
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );

        self::assertTrue( $tnRoot->has( 'Foo', true, false ) );
        self::assertTrue( $tnRoot->has( 'Foo$Bar', true, false ) );
        self::assertFalse( $tnRoot->has( 'Foo$Bar', false, false ) );
        self::assertTrue( $tnRoot->has( 'Foo${Bar}Baz', true, false ) );
        self::assertFalse( $tnRoot->has( 'Foo$Qux', true, false ) );
        self::assertTrue( $tnRoot->has( 'FooQuux', true, true ) );
    }


    public function testHasWithVariables() : void {
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addVariable( '$Bar', 'BAR' );
        $tnBar->addConstant( ' Baz', 'BAZ' );
        $tnBar->addConstant( ' Qux', 'QUX' );

        $r = [];
        self::assertSame( 'FOO', $root->get( 'Foo', $r, true ) );
        self::assertSame( 'BAR', $root->get( 'FooQuux', $r, true ) );
        self::assertSame( 'BAZ', $root->get( 'FooQuux Baz', $r, true ) );
        self::assertSame( 'QUX', $root->get( 'FooQuux Qux', $r, true ) );

        self::assertTrue( $root->has( 'Foo${Bar} Baz', true, false ) );
        self::assertTrue( $root->has( 'Foo${Bar} Qux', true, false ) );
        self::assertFalse( $root->has( 'Foo${Bar} Quux', true, false ) );
        self::assertFalse( $root->has( 'FooQux', true, false ) );

        self::assertFalse( $root->has( 'Foo${Bar} Baz', true, true ) );
        self::assertFalse( $root->has( 'Foo${Bar} Qux', true, true ) );
        self::assertFalse( $root->has( 'Foo${Bar} Quux', true, true ) );
        self::assertTrue( $root->has( 'FooQux', true, true ) );

    }


    public function testSetForExisting() : void {
        $tnRoot = new TrieNodeNavigator();
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
        $tnRoot->set( 'Foo${Bar}Baz', 'QUX', true );
    }


    public function testSetForNew() : void {
        $tnRoot = new TrieNodeNavigator();
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
        $root = new TrieNodeNavigator();
        $tnFoo = $root->addConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->addConstant( 'Bar', 'BAR' );
        $tnBar->addConstant( 'Baz', 'BAZ' );
        $tnBar->addVariable( '$Qux', 'QUX' );

        $root->unset( 'FooBar', false, false );
        self::assertNull( $root->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->xValue );
        self::assertSame( 'BAZ', $root->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rConstants[ 'Baz' ]->xValue );
        self::assertSame( 'QUX', $root->rConstants[ 'Foo' ]->rConstants[ 'Bar' ]->rVariables[ '$Qux' ]->xValue );
    }


    public function testUnsetForNotSet() : void {
        $tnRoot = new TrieNodeNavigator( 'BAZ' );
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
        $tnRoot = new TrieNodeNavigator();
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkConstant( 'Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );

        $tnRoot->unset( 'FooBar', true, true );
        self::assertSame( 'FOO', $tnRoot->rConstants[ 'Foo' ]->xValue );
        self::assertArrayNotHasKey( 'Bar', $tnRoot->rConstants[ 'Foo' ]->rConstants );
    }


    public function testUnsetForPrune2() : void {
        $root = new TrieNodeNavigator();
        $tnFoo = $root->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkConstant( 'Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );
        $tnBar->linkVariable( '$Qux', 'QUX' );
        $tnFoo->linkConstant( 'Quux', 'QUUX' );

        $root->unset( 'FooBar', true, true );
        self::assertArrayNotHasKey( 'Bar', $root->rConstants[ 'Foo' ]->rConstants );
    }


    public function testUnsetForVariables() : void {
        $tnRoot = new TrieNodeNavigator();
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


    public function testWalkForNoVariables() : void {
        $tnRoot = new TrieNodeNavigator();
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkConstant( 'Bar', 'BAR' );
        $tnBar->linkConstant( 'Baz', 'BAZ' );
        $walk = $tnRoot->walk( 'FooBarBaz', false, false );
        self::assertSame( 'FooBarBaz', $walk->path() );
        self::assertSame( '', $walk->stRest );

        $walk = $tnRoot->walk( 'FooBarBazQux', false, false );
        self::assertSame( 'FooBarBaz', $walk->path() );
        self::assertSame( 'Qux', $walk->stRest );

        $walk = $tnRoot->walk( 'FooBarQux', false, false );
        self::assertSame( 'FooBar', $walk->path() );
        self::assertSame( 'Qux', $walk->stRest );
    }


    public function testWalkForVariablesNoSubst() : void {
        $tnRoot = new TrieNodeNavigator();
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBaz = $tnBar->linkConstant( 'Baz', 'BAZ' );

        $walk = $tnRoot->walk( 'Foo$Bar', true, false );
        self::assertSame( 'Foo$Bar', $walk->path() );
        self::assertSame( '', $walk->stRest );
        self::assertSame( $tnBar, $walk->tsTail->tnTo );

        $walk = $tnRoot->walk( 'Foo${Bar}Baz', true, false );
        self::assertSame( 'Foo$BarBaz', $walk->path() );
        self::assertSame( '', $walk->stRest );
        self::assertSame( $tnBaz, $walk->tsTail->tnTo );

        $walk = $tnRoot->walk( 'Foo${Bar}BazQux', true, false );
        self::assertSame( 'Foo$BarBaz', $walk->path() );
        self::assertSame( 'Qux', $walk->stRest );

        $walk = $tnRoot->walk( 'Foo${Bar}Qux', true, false );
        self::assertSame( 'Foo$Bar', $walk->path() );
        self::assertSame( 'Qux', $walk->stRest );

        $walk = $tnRoot->walk( 'Foo${Qux}Baz', true, false );
        self::assertSame( 'Foo', $walk->path() );
        self::assertSame( '${Qux}Baz', $walk->stRest );
    }


    public function testWalkForVariablesWithSubst() : void {
        $tnRoot = new TrieNodeNavigator();
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBar->linkConstant( ' Baz', 'BAZ' );
        $walk = $tnRoot->walk( 'FooQux Baz', true, true );
        self::assertSame( 'Foo$Bar Baz', $walk->path() );
        self::assertSame( '', $walk->stRest );

        $walk = $tnRoot->walk( 'FooQuux BazQux', true, true );
        self::assertSame( 'Foo$Bar Baz', $walk->path() );
        self::assertSame( 'Qux', $walk->stRest );

        $walk = $tnRoot->walk( 'FooQuux Qux', true, true );
        self::assertSame( 'Foo$Bar', $walk->path() );
        self::assertSame( ' Qux', $walk->stRest );
    }


    public function testWalkWithVariableSubstMismatch() : void {
        $tnRoot = new TrieNodeNavigator();
        $tnFoo = $tnRoot->linkConstant( 'Foo', 'FOO' );
        $tnBar = $tnFoo->linkVariable( '$Bar', 'BAR' );
        $tnBar->linkConstant( ' Baz', 'BAZ' );
        $walk = $tnRoot->walk( 'Foo$Qux', true, true );
        self::assertSame( 'Foo', $walk->path() );
        self::assertSame( '$Qux', $walk->stRest );
    }


}