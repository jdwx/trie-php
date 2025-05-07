<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieMatch {


    /**
     * @param TrieNode $tn
     * @param string $stRest
     * @param array<string, string> $rMatches
     */
    public function __construct( public TrieNode $tn, public string $stRest, public array $rMatches ) {}


    public function path() : string {
        return join( '', $this->rMatches );
    }


}
