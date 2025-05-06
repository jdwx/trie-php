<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieNodeNavigator extends TrieNode {


    public function add( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables = false ) : static {
        $walk = $this->walk( $i_stPath, $i_bAllowVariables, false );
        if ( is_null( $walk->tsTail ) ) {
            $uPos = $i_bAllowVariables ? strpos( $i_stPath, '$' ) : false;
            if ( $uPos === false ) {
                return $this->addConstant( $i_stPath, $i_xValue );
            }
            $stConstant = substr( $i_stPath, 0, $uPos );
            [ $stVarName, $stRest ] = static::extractVariableName( substr( $i_stPath, $uPos ) );
            assert( is_string( $stVarName ) );
            assert( is_string( $stRest ) );
            $node = $this;
            if ( ! empty( $stConstant ) ) {
                $node = $node->addConstant( $stConstant, null );
            }
            if ( empty( $stRest ) ) {
                return $node->addVariable( $stVarName, $i_xValue );
            }
            $node = $node->addVariable( $stVarName, null );
            return $node->addConstant( $stRest, $i_xValue );
        }
        return static::cast( $walk->tsTail->tnTo )->add( $walk->stRest, $i_xValue, $i_bAllowVariables );
    }


    /** @param array<string, string> &$o_rVariables */
    public function get( string $i_stPath, array &$o_rVariables, bool $i_bAllowVariables = false ) : mixed {
        $walk = $this->walk( $i_stPath, $i_bAllowVariables, true );
        if ( ! empty( $walk->stRest ) ) {
            return null;
        }
        $o_rVariables = [];
        for ( $node = $walk->tsHead ; $node !== null ; $node = $node->tsNext ) {
            if ( str_starts_with( $node->stEdge, '$' ) ) {
                $o_rVariables[ $node->stEdge ] = $node->stMatch;
            }
        }
        return ( $walk->tsTail->tnTo ?? $this )->xValue;
    }


    public function has( string $i_stPath, bool $i_bAllowVariables, bool $i_bSubstituteVariables ) : bool {
        $walk = $this->walk( $i_stPath, $i_bAllowVariables, $i_bSubstituteVariables );
        if ( ! empty( $walk->stRest ) ) {
            return false;
        }
        $node = $walk->tsTail->tnTo ?? $this;
        return ! is_null( $node->xValue );
    }


    public function set( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables = false,
                         bool   $i_bOverwrite = false ) : static {
        $walk = $this->walk( $i_stPath, $i_bAllowVariables, false );
        if ( is_null( $walk->tsTail ) ) {
            if ( str_starts_with( $i_stPath, '$' ) ) {
                return $this->addVariable( $i_stPath, $i_xValue );
            }
            return $this->addConstant( $i_stPath, $i_xValue );
        }
        if ( ! empty( $walk->stRest ) ) {
            $tnChild = static::cast( $walk->tsTail->tnTo );
            return $tnChild->add( $walk->stRest, $i_xValue, $i_bAllowVariables );
        }
        $walk->tsTail->tnTo->setValue( $i_xValue, $i_bOverwrite );
        return static::cast( $walk->tsTail->tnTo );
    }


    public function unset( string $i_stPath, bool $i_bAllowVariables, bool $i_bPrune ) : void {
        $walk = $this->walk( $i_stPath, $i_bAllowVariables, false );
        if ( ! empty( $walk->stRest ) ) {
            return;
        }
        $node = $walk->tsTail->tnTo ?? $this;
        $node->unsetValue();
        while ( $node?->isDead() || $i_bPrune ) {
            $parent = $node->parent;
            $node->prune();
            $node = $parent;
            $i_bPrune = false;
        }
    }


    public function walk( string $i_stPath, bool $i_bAllowVariables, bool $i_bSubstituteVariables ) : TrieWalk {

        # Nothing left.
        if ( '' === $i_stPath ) {
            return new TrieWalk();
        }

        # First, look for a constant match.
        [ $stPrefix, $stKey, $stRest ] = $this->matchConstantPrefix( $i_stPath );
        if ( is_string( $stKey ) ) {
            assert( is_string( $stRest ) );
            assert( is_string( $stPrefix ) );
            $tnChild = $this->constantEx( $stKey );
            if ( $stKey === $stPrefix ) {
                $walkRest = $tnChild->walk( $stRest, $i_bAllowVariables, $i_bSubstituteVariables );
                $walkRest->prepend( $stKey, $stPrefix, $tnChild );
                return $walkRest;
            }
            $walk = new TrieWalk();
            $walk->stRest = $stPrefix . $stRest;
            return $walk;
        }

        if ( ! $i_bAllowVariables ) {
            # No variables allowed, so we are done.
            $walk = new TrieWalk();
            $walk->stRest = $i_stPath;
            return $walk;
        }

        if ( ! $i_bSubstituteVariables ) {
            # Second, look for a variable match without substitution.
            [ $stVarName, $stPathAfterVariable ] = self::extractVariableName( $i_stPath );
            if ( is_string( $stVarName ) && isset( $this->rVariables[ $stVarName ] ) ) {
                assert( is_string( $stPathAfterVariable ) );
                # We have a variable match.
                $tnChild = $this->variableEx( $stVarName );
                $walk = new TrieWalk();
                $walk->append( $stVarName, $stVarName, $tnChild );
                return $walk->merge( $tnChild->walk( $stPathAfterVariable, true, false ) );
            }
            $walk = new TrieWalk();
            $walk->stRest = $i_stPath;
            return $walk;
        }

        $rMatches = [];
        $rPoorMatches = [];
        foreach ( $this->variables() as $stVarName => $tnChild ) {
            # Third, look for a variable match with substitution.
            [ $stVarValue, $stPathAfterVariable ] = self::extractVariableValue( $stVarName, $i_stPath );
            if ( ! is_string( $stVarValue ) ) {
                continue;
            }
            # We have a variable match.
            $walk = $tnChild->walk( $stPathAfterVariable, true, true );
            $walk->prepend( $stVarName, $stVarValue, $tnChild );
            if ( '' === $walk->stRest ) {
                $rMatches[] = $walk;
            } else {
                $rPoorMatches[] = $walk;
            }
        }
        $uCount = count( $rMatches );
        if ( 1 === $uCount ) {
            return $rMatches[ 0 ];
        }
        if ( 0 === $uCount ) {
            $uCount = count( $rPoorMatches );
            if ( 1 === $uCount ) {
                return $rPoorMatches[ 0 ];
            }
            if ( 0 === $uCount ) {
                # No matches, so we are done.
                $walk = new TrieWalk();
                $walk->stRest = $i_stPath;
                return $walk;
            }
        }

        # Multiple matches; ambiguity is not allowed.
        $rPaths = [];
        foreach ( $rMatches as $walk ) {
            $rPaths[] = $walk->path() . '|' . $walk->stRest;
        }
        throw new \RuntimeException(
            'Ambiguous variable match for "' . $i_stPath . '" in ' . join( ', ', $rPaths )
        );
    }


}