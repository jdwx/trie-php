<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieMatch {


    /**
     * @param TrieNode $tn
     * @param string $stRest
     * @param list<TriePair> $rMatches
     */
    public function __construct( public TrieNode $tn, public string $stRest, public array $rMatches ) {}


    public function key() : string {
        $r = [];
        foreach ( $this->matches() as $stKey => $stMatch ) {
            $r[] = $stKey;
        }
        return join( '', $r );
    }


    /** @return iterable<string, string> */
    public function matches() : iterable {
        foreach ( $this->rMatches as $tp ) {
            yield $tp->stKey => $tp->stMatch;
        }
    }


    public function path() : string {
        $r = [];
        foreach ( $this->matches() as $stMatch ) {
            $r[] = $stMatch;
        }
        return join( '', $r );
    }


    public function rest() : string {
        return $this->stRest;
    }


    public function value() : mixed {
        return $this->tn->xValue;
    }


}
