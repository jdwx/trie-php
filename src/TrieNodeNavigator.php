<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieNodeNavigator extends TrieNode {


    /*
    public function add( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables = false ) : TrieNode {
        $node = $this->getClosestConstant( $i_stPath );
        return $node->addConstant( $i_stPath, $i_xValue, $i_bAllowVariables );
    }


    public function addInner( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables,
                              bool   $i_bAllowOverwrite ) : static {
        # If the path is empty, you are adding a value to the current node.
        if ( '' === $i_stPath ) {
            if ( null === $this->xValue || $i_bAllowOverwrite ) {
                # We can overwrite the value.
                $this->xValue = self::asNotNode( $i_xValue );
                return $this;
            }

            # Otherwise, this is a collision, and keys must be unique.
            throw new InvalidArgumentException( 'Key already exists.' );
        }

        if ( $i_bAllowVariables ) {
            if ( str_starts_with( $i_stPath, '$' ) ) {
                # This is a variable path.
                [ $nstVarName, $stPath ] = self::extractVariableName( $i_stPath );
                if ( is_string( $nstVarName ) && is_string( $stPath ) ) {
                    return $this->addVariableChildDeep( $nstVarName, $stPath, $i_xValue, $i_bAllowOverwrite );
                }
            }

            if ( str_contains( $i_stPath, '$' ) ) {
                # There may be a variable later in the path, but it is not at the start.
                $uPos = strpos( $i_stPath, '$' );
                $stPathBeforeVariable = substr( $i_stPath, 0, $uPos );
                $stPathAfterVariable = substr( $i_stPath, $uPos );
                [ $stVarName, $stPathAfterVariable ] = self::extractVariableName( $stPathAfterVariable );
                if ( is_string( $stVarName ) && is_string( $stPathAfterVariable ) ) {
                    # We have a variable name.
                    $tnChild = $this->addInner( $stPathBeforeVariable, null, false, $i_bAllowOverwrite );
                    return $tnChild->addVariableChildDeep( $stVarName, $stPathAfterVariable, $i_xValue,
                        $i_bAllowOverwrite );
                }
                # It wasn't actually a variable name, so continue.
            }
        }

        [ $stPrefix, $stMatch ] = $this->matchConstantPrefix( $i_stPath );
        if ( ! is_string( $stPrefix ) ) {
            # No match, so we can add the new child.
            $this->rConstants[ $i_stPath ] = self::asNode( $i_xValue );
            return $this->rConstants[ $i_stPath ];
        }
        assert( is_string( $stMatch ) );

        # There is a prefix match. There are three cases to deal with:
        # 1. A common prefix (e.g., "FooBar" and "FooQux")
        # 2. A prefix match with the existing child (e.g., "Foo" and "FooBar")
        # 3. A prefix match with the new child (e.g., "FooBar" and "Foo")

        # Case 1: A common prefix.
        if ( $stMatch !== $stPrefix && $stPrefix !== $i_stPath ) {
            # Create a new child node for the common prefix.
            $tnSplit = new TrieNodeNavigator( null, $this );
            $this->rConstants[ $stPrefix ] = $tnSplit;
            $tnSplit->addInner( substr( $stMatch, strlen( $stPrefix ) ), $this->rConstants[ $stMatch ],
                $i_bAllowVariables, $i_bAllowOverwrite );
            unset( $this->rConstants[ $stMatch ] );
            return $tnSplit->addInner( substr( $i_stPath, strlen( $stPrefix ) ), self::asNode( $i_xValue ),
                $i_bAllowVariables, $i_bAllowOverwrite );
        }

        # Case 2: Existing child is a prefix match.
        if ( $stMatch === $stPrefix ) {
            # Add the new child to the existing child.
            $i_stPath = substr( $i_stPath, strlen( $stPrefix ) );
            return $this->constant( $stMatch )->addInner( $i_stPath, $i_xValue, $i_bAllowVariables,
                $i_bAllowOverwrite );
        }

        # Case 3: New child is a prefix match.
        assert( $stPrefix === $i_stPath );
        $tnChild = $this->rConstants[ $stMatch ];
        unset( $this->rConstants[ $stMatch ] );
        $tnNew = $this->addInner( $stPrefix, $i_xValue, $i_bAllowVariables, $i_bAllowOverwrite );
        $tnNew->addInner( substr( $stMatch, strlen( $stPrefix ) ), $tnChild, $i_bAllowVariables, $i_bAllowOverwrite );
        return $tnNew;
    }


    public function addVariableChildDeep( string $i_stVarName, string $i_stPath, mixed $i_xValue,
                                          bool   $i_bAllowOverwrite ) : static {
        if ( isset( $this->rVariables[ $i_stVarName ] ) ) {
            # We already have a variable child with this name.
            return $this->variable( $i_stVarName )->addInner( $i_stPath, $i_xValue,
                true, $i_bAllowOverwrite );
        }
        # Create a new child node for the variable.
        $tnChild = new static( null, $this );
        $this->rVariables[ $i_stVarName ] = $tnChild;
        return $tnChild->addInner( $i_stPath, $i_xValue, true, $i_bAllowOverwrite );
    }


    public function setDeep( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables = false ) : TrieNode {
        return $this->addInner( $i_stPath, $i_xValue, $i_bAllowVariables, true );
    }

    */


    public function getClosestConstant( string &$io_stPath ) : static {
        $tnChild = $this->getConstant( $io_stPath );
        if ( null === $tnChild ) {
            return $this;
        }
        return $tnChild->getClosestConstant( $io_stPath );
    }


    /**
     * Get the closest node for a given path, allowing for variable names.
     * (Variable names are matched as literals.)
     */
    public function getClosestForSet( string &$io_stPath, bool $i_bAllowVariables ) : static {
        if ( '' === $io_stPath ) {
            # No path, so we are at the current node.
            return $this;
        }
        $node = $this->getClosestConstant( $io_stPath );

        # If variables are not allowed, then this is the end of the road.
        if ( ! $i_bAllowVariables ) {
            return $node;
        }

        [ $stVarName, $stPathAfterVariable ] = self::extractVariableName( $io_stPath );
        if ( ! is_string( $stVarName ) || ! is_string( $stPathAfterVariable ) ) {
            # Not a variable name.
            return $node;
        }

        if ( ! isset( $node->rVariables[ $stVarName ] ) ) {
            # No variable child with this name.
            return $node;
        }

        $io_stPath = $stPathAfterVariable;
        return $node->variable( $stVarName )->getClosestForSet( $io_stPath, true );
    }


}