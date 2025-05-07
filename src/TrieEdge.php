<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieEdge {


    public function __construct( public string $stEdge, public string $stMatch, public TrieNode $tnTo ) {}


}
