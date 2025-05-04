<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class TrieNode {


    /** @var array<string, TrieNode> */
    public array $rChildren = [];

    /** @var array<string, TrieNode> */
    public array $rVariableChildren = [];


    public function __construct( public mixed $xValue = null ) {}


    public static function asNode( mixed $i_xValue ) : TrieNode {
        if ( $i_xValue instanceof TrieNode ) {
            return $i_xValue;
        }
        return new TrieNode( $i_xValue );
    }


    public static function asNotNode( mixed $i_xValue ) : mixed {
        if ( $i_xValue instanceof TrieNode ) {
            return $i_xValue->xValue;
        }
        return $i_xValue;
    }


    public static function commonPrefix( string $i_st1, string $i_st2 ) : string {
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        for ( $i = 0 ; $i < strlen( $i_st1 ) && $i < strlen( $i_st2 ) && $i_st1[ $i ] === $i_st2[ $i ] ; ++$i ) {
        }
        return substr( $i_st1, 0, $i );
    }


    /** @return list<?string> */
    public static function extractVariableName( string $i_stPath ) : array {
        if ( ! str_starts_with( $i_stPath, '$' ) ) {
            return [ null, $i_stPath ];
        }

        if ( str_starts_with( $i_stPath, '${' ) ) {
            # Variable is of the form ${varName}
            $uPos = strpos( $i_stPath, '}' );
            if ( false === $uPos ) {
                return [ null, $i_stPath ];
            }
            $stVarName = '$' . substr( $i_stPath, 2, $uPos - 2 );
            if ( '$' === $stVarName ) {
                return [ null, $i_stPath ];
            }
            $stPath = substr( $i_stPath, $uPos + 1 );
            return [ $stVarName, $stPath ];
        }

        # Variable is of the form $varName
        # We will allow alphanumeric characters and underscores.
        $rMatches = [];
        if ( ! preg_match( '/^(\$[a-zA-Z_]+)(.*)$/', $i_stPath, $rMatches ) ) {
            return [ null, $i_stPath ];
        }
        $stVarName = $rMatches[ 1 ];
        $stPath = $rMatches[ 2 ];
        return [ $stVarName, $stPath ];
    }


    public function add( string $i_stPath, mixed $i_xValue, bool $i_bAllowVariables ) : TrieNode {
        # If the path is empty, you are adding a value to the current node.
        if ( '' === $i_stPath ) {
            if ( null === $this->xValue ) {
                # We can overwrite the value.
                $this->xValue = self::asNotNode( $i_xValue );
                return $this;
            }

            # Otherwise, this is a collision, and keys must be unique.
            throw new \InvalidArgumentException( 'Key already exists.' );
        }

        if ( $i_bAllowVariables ) {
            if ( str_starts_with( $i_stPath, '$' ) ) {
                # This is a variable path.
                [ $nstVarName, $stPath ] = self::extractVariableName( $i_stPath );
                if ( is_string( $nstVarName ) && is_string( $stPath ) ) {
                    return $this->addVariableChild( $nstVarName, $stPath, $i_xValue );
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
                    $tnChild = $this->add( $stPathBeforeVariable, null, false );
                    return $tnChild->addVariableChild( $stVarName, $stPathAfterVariable, $i_xValue );
                }
                # It wasn't actually a variable name, so continue.
            }
        }

        [ $stPrefix, $stMatch ] = $this->matchPrefix( $i_stPath );
        if ( ! is_string( $stPrefix ) ) {
            # No match, so we can add the new child.
            $this->rChildren[ $i_stPath ] = self::asNode( $i_xValue );
            return $this->rChildren[ $i_stPath ];
        }
        assert( is_string( $stMatch ) );

        # There is a prefix match. There are three cases to deal with:
        # 1. A common prefix (e.g., "foobar" and "fooqux")
        # 2. A prefix match with the existing child (e.g., "foo" and "foobar")
        # 3. A prefix match with the new child (e.g., "foobar" and "foo")

        # Case 1: A common prefix.
        if ( $stMatch !== $stPrefix && $stPrefix !== $i_stPath ) {
            # Create a new child node for the common prefix.
            $tnSplit = new TrieNode();
            $this->rChildren[ $stPrefix ] = $tnSplit;
            $tnSplit->add( substr( $stMatch, strlen( $stPrefix ) ), $this->rChildren[ $stMatch ], $i_bAllowVariables );
            unset( $this->rChildren[ $stMatch ] );
            return $tnSplit->add( substr( $i_stPath, strlen( $stPrefix ) ), self::asNode( $i_xValue ), $i_bAllowVariables );
        }

        # Case 2: Existing child is a prefix match.
        if ( $stMatch === $stPrefix ) {
            # Add the new child to the existing child.
            $i_stPath = substr( $i_stPath, strlen( $stPrefix ) );
            return $this->rChildren[ $stMatch ]->add( $i_stPath, $i_xValue, $i_bAllowVariables );
        }

        # Case 3: New child is a prefix match.
        assert( $stPrefix === $i_stPath );
        $tnChild = $this->rChildren[ $stMatch ];
        unset( $this->rChildren[ $stMatch ] );
        $tnNew = $this->add( $stPrefix, $i_xValue, $i_bAllowVariables );
        $tnNew->add( substr( $stMatch, strlen( $stPrefix ) ), $tnChild, $i_bAllowVariables );
        return $tnNew;
    }


    public function addVariableChild( string $i_stVarName, string $i_stPath, mixed $i_xValue ) : TrieNode {
        if ( isset( $this->rVariableChildren[ $i_stVarName ] ) ) {
            # We already have a variable child with this name.
            return $this->rVariableChildren[ $i_stVarName ]->add( $i_stPath, $i_xValue, true );
        }
        # Create a new child node for the variable.
        $tnChild = new TrieNode();
        $this->rVariableChildren[ $i_stVarName ] = $tnChild;
        return $tnChild->add( $i_stPath, $i_xValue, true );
    }


    public function get( string &$io_stPath ) : TrieNode {
        $tnChild = $this->getChild( $io_stPath );
        if ( null === $tnChild ) {
            return $this;
        }
        return $tnChild->get( $io_stPath );
    }


    public function getChild( string &$io_stPath ) : ?TrieNode {
        foreach ( $this->rChildren as $stNodePath => $tnChild ) {
            if ( str_starts_with( $io_stPath, $stNodePath ) ) {
                $io_stPath = substr( $io_stPath, strlen( $stNodePath ) );
                return $tnChild;
            }
        }
        return null;
    }


    /** @return list<string> */
    public function listVariableChildren() : array {
        return array_map( fn( $x ) => strval( $x ), array_keys( $this->rVariableChildren ) );
    }


    /** @return list<?string> */
    public function matchPrefix( string $i_stPath ) : array {
        foreach ( $this->rChildren as $stNodePath => $tnChild ) {
            $stPrefix = self::commonPrefix( $stNodePath, $i_stPath );
            if ( '' === $stPrefix ) {
                continue;
            }
            return [ $stPrefix, $stNodePath ];
        }
        return [ null, null ];
    }


}
