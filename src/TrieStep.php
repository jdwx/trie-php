<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieStep extends TrieEdge {


    public function __construct( string           $stEdge, string $stMatch, TrieNode $tnTo,
                                 public ?TrieStep $tsPrev = null, public ?TrieStep $tsNext = null ) {
        parent::__construct( $stEdge, $stMatch, $tnTo );
    }


}
