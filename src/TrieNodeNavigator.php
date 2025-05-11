<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


use LogicException;


class TrieNodeNavigator extends TrieNode {


    /**
     * @param string $i_stHaystack
     * @param string $i_stNeedle
     * @return iterable<string, string>
     */
    public static function listOffsetPairs( string $i_stHaystack, string $i_stNeedle ) : iterable {
        foreach ( self::listStringOffsets( $i_stHaystack, $i_stNeedle ) as $u ) {
            $stPrefix = substr( $i_stHaystack, 0, $u );
            $stSuffix = substr( $i_stHaystack, $u );
            if ( '' === $stPrefix ) {
                continue;
            }
            yield $stPrefix => $stSuffix;
        }
    }


    /**
     * @param string $i_stHaystack
     * @param string $i_stNeedle
     * @return iterable<int>
     */
    public static function listStringOffsets( string $i_stHaystack, string $i_stNeedle ) : iterable {
        for ( $u = 0 ; $u <= strlen( $i_stHaystack ) - strlen( $i_stNeedle ) ; $u++ ) {
            if ( str_starts_with( substr( $i_stHaystack, $u ), $i_stNeedle ) ) {
                yield $u;
            }
        }
    }


    public function add( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables, bool $i_bAllowOverwrite ) : static {
        $match = $this->matchOne( $i_stPath, $i_bAllowVariables, false );
        if ( is_null( $match ) ) {
            $tnChild = $this;
            $stRest = $i_stPath;
        } else {
            $tnChild = static::cast( $match->tn );
            $stRest = $match->stRest;
        }
        $uPos = $i_bAllowVariables ? strpos( $stRest, '$' ) : false;
        if ( false === $uPos ) {
            return static::cast( $tnChild->addConstant( $stRest, $i_xValue, $i_bAllowOverwrite ) );
        }
        $stConstant = substr( $stRest, 0, $uPos );
        [ $stVarName, $stRest ] = static::extractVariableName( substr( $stRest, $uPos ) );
        assert( is_string( $stVarName ) );
        assert( is_string( $stRest ) );

        if ( '' !== $stConstant ) {
            $tnChild = $tnChild->addConstant( $stConstant, null, $i_bAllowOverwrite );
        }

        if ( empty( $stRest ) ) {
            return static::cast( $tnChild->addVariable( $stVarName, $i_xValue, $i_bAllowOverwrite ) );
        }
        $tnChild = $tnChild->addVariable( $stVarName, null, $i_bAllowOverwrite );
        return static::cast( $tnChild->add( $stRest, $i_xValue, $i_bAllowVariables, $i_bAllowOverwrite ) );
    }


    /**
     * @return iterable<string, string>
     */
    public function findMatchesAfterVariable( string $i_stPath ) : iterable {
        if ( ! empty( $this->rVariables ) ) {
            throw new LogicException( 'Adjacent variables are ambiguous.' );
        }
        if ( '' !== $i_stPath ) {
            yield $i_stPath => '';
        }
        # For each constant key, find each occurrence of it in the path.
        # Offer each occurrence as a possible match for a variable
        # substitution followed by this node.
        foreach ( $this->constants() as $stKey => $tnChild ) {
            yield from self::listOffsetPairs( $i_stPath, $stKey );
        }
    }


    /** @param array<string, string|list<string>> &$o_rVariables */
    public function get( string  $i_stPath, array &$o_rVariables, bool $i_bAllowVariables,
                         ?string &$o_nstExtra = null ) : mixed {
        $o_rVariables = [];
        $match = $this->matchOne( $i_stPath, $i_bAllowVariables, true );
        if ( is_null( $match ) ) {
            return null;
        }
        if ( ! empty( $match->stRest ) ) {
            if ( ! is_string( $o_nstExtra ) ) {
                return null;
            }
            $o_nstExtra = $match->stRest;
        }
        foreach ( $match->rMatches as $tp ) {
            if ( $tp->stKey !== $tp->stMatch ) {
                if ( isset( $o_rVariables[ $tp->stKey ] ) ) {
                    if ( ! is_array( $o_rVariables[ $tp->stKey ] ) ) {
                        $o_rVariables[ $tp->stKey ] = [ $o_rVariables[ $tp->stKey ] ];
                    }
                    /** @phpstan-ignore-next-line */
                    $o_rVariables[ $tp->stKey ][] = $tp->stMatch;
                } else {
                    $o_rVariables[ $tp->stKey ] = $tp->stMatch;
                }
            }
        }
        return $match->tn->xValue;
    }


    public function has( string $i_stPath, bool $i_bAllowVariables, bool $i_bSubstituteVariables,
                         bool   $i_bAllowExtra ) : bool {
        $match = $this->matchOne( $i_stPath, $i_bAllowVariables, $i_bSubstituteVariables );
        if ( is_null( $match ) ) {
            return false;
        }
        if ( $match->stRest && ! $i_bAllowExtra ) {
            return false;
        }
        return ! is_null( $match->tn->xValue );
    }


    /**
     * @param list<TriePair> $i_rMatches
     * @return iterable<TrieMatch>
     */
    public function match( string $i_stMatch, bool $i_bAllowVariables, bool $i_bExpandVariables, array $i_rMatches ) : iterable {
        # We can always match nothing and treat the rest as extra.
        if ( ! is_null( $this->xValue ) ) {
            yield new TrieMatch( $this, $i_stMatch, $i_rMatches );
        }
        foreach ( $this->constants() as $stKey => $tnChild ) {
            if ( str_starts_with( $i_stMatch, $stKey ) ) {
                $stRest = substr( $i_stMatch, strlen( $stKey ) );
                $rMatches = $i_rMatches;
                $rMatches[] = new TriePair( $stKey, $stKey );
                yield from $tnChild->match( $stRest, $i_bAllowVariables, $i_bExpandVariables, $rMatches );
            }
        }
        if ( ! $i_bAllowVariables || empty( $this->rVariables ) ) {
            return;
        }

        if ( ! $i_bExpandVariables ) {
            [ $stVarName, $stPathAfterVariable ] = self::extractVariableName( $i_stMatch );
            if ( is_string( $stVarName ) && $this->hasVariable( $stVarName ) ) {
                assert( is_string( $stPathAfterVariable ) );
                $rMatches = $i_rMatches;
                $rMatches[] = new TriePair( $stVarName, $stVarName );
                yield from $this->variableEx( $stVarName )->match(
                    $stPathAfterVariable, true, false, $rMatches
                );
            }
            return;
        }

        foreach ( $this->variables() as $stVarName => $tnChild ) {
            foreach ( $tnChild->findMatchesAfterVariable( $i_stMatch ) as $stVarValue => $stSuffix ) {
                $rMatches = $i_rMatches;
                $rMatches[] = new TriePair( $stVarName, $stVarValue );
                yield from $tnChild->match( $stSuffix, true, true, $rMatches );
            }
        }

    }


    public function matchOne( string $i_stMatch, bool $i_bAllowVariables, bool $i_bExpandVariables ) : ?TrieMatch {
        $uMaxScore = 0;
        $rMatches = [];
        foreach ( $this->match( $i_stMatch, $i_bAllowVariables, $i_bExpandVariables, [] ) as $tm ) {
            /** @phpstan-ignore-next-line */
            assert( $tm instanceof TrieMatch );
            $uScore = '' === $tm->stRest ? 1 : 0;
            foreach ( $tm->matches() as $stKey => $stMatch ) {
                if ( $stKey === $stMatch ) {
                    $uScore += 100000;
                } else {
                    $uScore += 1;
                }
            }
            if ( $uScore < $uMaxScore ) {
                continue;
            }
            if ( $uScore > $uMaxScore ) {
                $rMatches = [];
                $uMaxScore = $uScore;
            }
            $rMatches[] = $tm;
        }
        if ( empty( $rMatches ) ) {
            return null;
        }
        if ( 1 === count( $rMatches ) ) {
            return $rMatches[ 0 ];
        }

        $rPaths = [];
        foreach ( $rMatches as $tm ) {
            $stPath = $tm->path() . ( '' === $tm->stRest ? '' : "|{$tm->stRest}" );
            $rPaths[] = $stPath;
        }
        throw new \RuntimeException(
            'Ambiguous variable match for "' . $i_stMatch . '" in: ' . join( ', ', $rPaths )
        );
    }


    public function set( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables, bool $i_bOverwrite ) : static {
        $match = $this->matchOne( $i_stPath, $i_bAllowVariables, false );
        if ( is_null( $match ) ) {
            return $this->add( $i_stPath, $i_xValue, $i_bAllowVariables, $i_bOverwrite );
        }
        $tnChild = static::cast( $match->tn );
        if ( ! empty( $match->stRest ) ) {
            return $tnChild->add( $match->stRest, $i_xValue, $i_bAllowVariables, $i_bOverwrite );
        }
        $tnChild->setValue( $i_xValue, $i_bOverwrite );
        return $tnChild;
    }


    public function unset( string $i_stPath, bool $i_bAllowVariables, bool $i_bPrune ) : void {
        $match = $this->matchOne( $i_stPath, $i_bAllowVariables, false );
        if ( is_null( $match ) || $match->stRest ) {
            return;
        }
        $node = static::cast( $match->tn );
        $node->unsetValue();
        while ( $node?->isDead() || $i_bPrune ) {
            $parent = $node->parent;
            $node->prune();
            $node = $parent;
            $i_bPrune = false;
        }
    }


}