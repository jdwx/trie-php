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


    /**
     * @return array<string, string|list<string>>
     */
    public function variables() : array {
        $r = [];
        foreach ( $this->rMatches as $tp ) {
            if ( $tp->stKey !== $tp->stMatch ) {
                if ( isset( $r[ $tp->stKey ] ) ) {
                    if ( ! is_array( $r[ $tp->stKey ] ) ) {
                        $r[ $tp->stKey ] = [ $r[ $tp->stKey ] ];
                    }
                    /** @phpstan-ignore-next-line */
                    $r[ $tp->stKey ][] = $tp->stMatch;
                } else {
                    $r[ $tp->stKey ] = $tp->stMatch;
                }
            }
        }
        return $r;
    }


}
