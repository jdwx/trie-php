<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieWalk {


    public ?TrieStep $tsHead = null;

    public ?TrieStep $tsTail = null;

    public string $stRest = '';


    public function append( string $i_stEdge, string $i_stMatch, TrieNode $i_tnTo ) : TrieStep {
        $ts = new TrieStep( $i_stEdge, $i_stMatch, $i_tnTo, $this->tsTail );
        if ( $this->tsTail !== null ) {
            /** @noinspection PhpFieldImmediatelyRewrittenInspection */
            $this->tsTail->tsNext = $ts;
        }
        $this->tsTail = $ts;
        if ( $this->tsHead === null ) {
            $this->tsHead = $ts;
        }
        return $ts;
    }


    public function get() : mixed {
        return $this->tsTail?->tnTo->xValue;
    }


    public function merge( TrieWalk $i_walk ) : static {
        $this->tsTail->tsNext = $i_walk->tsHead;
        $i_walk->tsHead = null;
        $i_walk->tsTail = null;
        $this->stRest .= $i_walk->stRest;
        return $this;
    }


    public function path() : string {
        $stPath = '';
        $ts = $this->tsHead;
        while ( $ts !== null ) {
            $stPath .= $ts->stEdge;
            $ts = $ts->tsNext;
        }
        return $stPath;
    }


    public function prepend( string $i_stEdge, string $i_stMatch, TrieNode $i_tnTo ) : TrieStep {
        $ts = new TrieStep( $i_stEdge, $i_stMatch, $i_tnTo, null, $this->tsHead );
        if ( $this->tsHead !== null ) {
            /** @noinspection PhpFieldImmediatelyRewrittenInspection */
            $this->tsHead->tsPrev = $ts;
        }
        $this->tsHead = $ts;
        if ( $this->tsTail === null ) {
            $this->tsTail = $ts;
        }
        return $ts;
    }


    /**
     * @return void
     *
     * Roll back any steps with null values at the end of the walk.
     *
     * @suppress PhanPossiblyInfiniteRecursionSameParams
     */
    public function rollback() : void {
        if ( $this->tsTail === null ) {
            return;
        }
        if ( ! is_null( $this->tsTail->tnTo->xValue ) ) {
            return;
        }
        $this->stRest = $this->tsTail->stEdge . $this->stRest;
        $this->tsTail = $this->tsTail->tsPrev;
        if ( $this->tsTail === null ) {
            $this->tsHead = null;
            return;
        }
        $this->rollback();
    }


}
