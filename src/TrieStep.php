<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieStep {


    public function __construct( public string    $stEdge, public string $stMatch, public TrieNode $tnTo,
                                 public ?TrieStep $tsPrev = null, public ?TrieStep $tsNext = null ) {}


}
