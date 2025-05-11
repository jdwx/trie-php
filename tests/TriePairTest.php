<?php


declare( strict_types = 1 );


use JDWX\Trie\TriePair;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TriePair::class )]
final class TriePairTest extends TestCase {


    public function testConstruct() : void {
        $pair = new TriePair( 'Foo', 'Bar' );
        self::assertSame( 'Foo', $pair->stKey );
        self::assertSame( 'Bar', $pair->stMatch );
    }


}
