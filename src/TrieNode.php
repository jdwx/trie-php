<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


use InvalidArgumentException;
use LogicException;


/**
 * TrieNode is the base class for the radix tree implementation. It provides
 * only the basic functionality for node operations. I.e., methods that
 * operate on the node itself or its relationships to its parent or
 * children.
 *
 * It has two types of children: constant and variable nodes.
 */
class TrieNode {


    /** @var ?callable */
    public static $fnExtractVarName = null;

    /** @var ?callable */
    public static $fnExtractVarValue = null;

    protected static string $reVariableValue = '/^([-a-zA-Z0-9_.]+)(.*)$/';


    /** @var array<string, TrieNode> */
    public array $rConstants = [];

    /** @var array<string, TrieNode> */
    public array $rVariables = [];


    final public function __construct( public mixed $xValue = null, public ?TrieNode $parent = null ) {}


    public static function asNode( mixed $i_xValue, ?TrieNode $parent = null ) : static {
        if ( $i_xValue instanceof static ) {
            $i_xValue->parent = $parent;
            return $i_xValue;
        }
        return new static( $i_xValue, $parent );
    }


    public static function asNotNode( mixed $i_xValue ) : mixed {
        if ( $i_xValue instanceof static ) {
            return $i_xValue->xValue;
        }
        return $i_xValue;
    }


    /** @noinspection PhpConditionAlreadyCheckedInspection */
    public static function cast( TrieNode $i_node ) : static {
        if ( $i_node instanceof static ) {
            return $i_node;
        }
        throw new InvalidArgumentException( 'Invalid node type' );
    }


    public static function commonPrefix( string $i_st1, string $i_st2 ) : string {
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        for ( $i = 0 ; $i < strlen( $i_st1 ) && $i < strlen( $i_st2 ) && $i_st1[ $i ] === $i_st2[ $i ] ; ++$i ) {
        }
        return substr( $i_st1, 0, $i );
    }


    /** @return list<?string> */
    public static function defaultExtractVariableName( string $i_stPath ) : array {
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


    /**
     * @return list<?string>
     *
     *
     */
    public static function defaultExtractVariableValue( string $i_stPath ) : array {
        $rMatches = [];
        if ( ! preg_match( static::$reVariableValue, $i_stPath, $rMatches ) ) {
            return [ null, $i_stPath ];
        }
        $stValue = $rMatches[ 1 ];
        $stPath = $rMatches[ 2 ];
        return [ $stValue, $stPath ];
    }


    /** @return list<?string> */
    public static function extractVariableName( string $i_stPath ) : array {
        if ( is_callable( static::$fnExtractVarName ) ) {
            return ( static::$fnExtractVarName )( $i_stPath );
        }
        return static::defaultExtractVariableName( $i_stPath );
    }


    public static function extractVariableValue( string $i_stVarName, string $i_stPath ) : mixed {
        if ( is_callable( static::$fnExtractVarValue ) ) {
            return ( static::$fnExtractVarValue )( $i_stVarName, $i_stPath );
        }
        return static::defaultExtractVariableValue( $i_stPath );
    }


    /**
     * @param array<string, TrieNode> $i_rOptions
     * @return list<?string>
     */
    public static function matchPrefix( array $i_rOptions, string $i_stKey ) : array {
        foreach ( $i_rOptions as $stNodePath => $tn ) {
            $stPrefix = static::commonPrefix( $stNodePath, $i_stKey );
            if ( '' === $stPrefix ) {
                continue;
            }
            return [ $stPrefix, $stNodePath, substr( $i_stKey, strlen( $stPrefix ) ) ];
        }
        return [ null, null, null ];

    }


    public function addConstant( string $i_stKey, mixed $i_xValue, bool $i_bAllowOverwrite = false ) : static {
        [ $stPrefix, $stKey, $stRest ] = $this->matchConstantPrefix( $i_stKey );
        if ( ! is_string( $stPrefix ) ) {
            # No match. Add the new constant directly.
            return $this->linkConstant( $i_stKey, $i_xValue );
        }
        assert( is_string( $stKey ) );
        assert( is_string( $stRest ) );

        # Exact match. We can (try to) set the existing value.
        if ( $i_stKey === $stKey ) {
            $tnc = $this->constantEx( $i_stKey );
            $tnc->setValue( $i_xValue, $i_bAllowOverwrite );
            return $tnc;
        }

        # There is a prefix match. We need to split the constant.
        return $this->splitConstant( $stPrefix, $stKey, $stRest, $i_xValue );
    }


    public function addVariable( string $i_stKey, mixed $i_xValue, bool $i_bAllowOverwrite = false ) : static {
        if ( isset( $this->rVariables[ $i_stKey ] ) &&
            ! is_null( $this->rVariables[ $i_stKey ]->xValue ) && ! $i_bAllowOverwrite ) {
            throw new InvalidArgumentException( "Variable node at '{$i_stKey}' already has a value" );
        }
        $var = $this->variable( $i_stKey );
        if ( $var ) {
            # We have a variable node already, so we can set the value.
            $var->xValue = static::asNotNode( $i_xValue );
            return $var;
        }
        # We don't have a matching variable node, so we need to create one.
        $tnv = new static( static::asNotNode( $i_xValue ), $this );
        $this->rVariables[ $i_stKey ] = $tnv;
        return $tnv;
    }


    public function constant( string $i_stKey ) : ?static {
        /** @phpstan-ignore-next-line */
        return $this->rConstants[ $i_stKey ] ?? null;
    }


    public function constantEx( string $i_stKey ) : static {
        if ( isset( $this->rConstants[ $i_stKey ] ) ) {
            /** @phpstan-ignore-next-line */
            return $this->rConstants[ $i_stKey ];
        }
        throw new InvalidArgumentException( "Constant node at '{$i_stKey}' does not exist" );
    }


    /** @return iterable<string, static>
     * @noinspection PhpCastIsUnnecessaryInspection
     */
    public function constants() : iterable {
        foreach ( $this->rConstants as $stKey => $tn ) {
            yield strval( $stKey ) => static::cast( $tn );
        }
    }


    public function findParentKey() : ?string {
        if ( ! $this->parent instanceof TrieNode ) {
            return null;
        }
        foreach ( $this->parent->rConstants as $stKey => $tn ) {
            if ( $tn === $this ) {
                return $stKey;
            }
        }
        foreach ( $this->parent->rVariables as $stKey => $tn ) {
            if ( $tn === $this ) {
                return $stKey;
            }
        }
        throw new LogicException( 'This should be unreachable!' );
    }


    public function findPath() : string {
        if ( ! $this->parent instanceof TrieNode ) {
            return '';
        }
        return $this->parent->findPath() . $this->findParentKey();
    }


    public function getConstant( string &$io_stPath ) : ?static {
        foreach ( $this->constants() as $stNodePath => $tnc ) {
            if ( str_starts_with( $io_stPath, $stNodePath ) ) {
                if ( $tnc->isDead() ) {
                    # This is a dead node. We can prune it.
                    unset( $this->rConstants[ $stNodePath ] );
                    continue;
                }
                $io_stPath = substr( $io_stPath, strlen( $stNodePath ) );
                return $tnc;
            }
        }
        return null;
    }


    public function isDead() : bool {
        return is_null( $this->xValue ) && empty( $this->rConstants ) && empty( $this->rVariables );
    }


    public function linkConstant( string $i_stKey, mixed $tnc ) : static {
        if ( isset( $this->rConstants[ $i_stKey ] ) ) {
            throw new InvalidArgumentException( "Constant node at '{$i_stKey}' already exists" );
        }
        if ( ! $tnc instanceof static ) {
            $tnc = new static( $tnc, $this );
        } else {
            $tnc->parent = $this;
        }
        return $this->rConstants[ $i_stKey ] = $tnc;
    }


    public function linkVariable( string $i_stVarName, mixed $tnv ) : static {
        if ( isset( $this->rVariables[ $i_stVarName ] ) ) {
            throw new InvalidArgumentException( "Variable node at '{$i_stVarName}' already exists" );
        }
        if ( ! $tnv instanceof static ) {
            $tnv = new static( $tnv, $this );
        } else {
            $tnv->parent = $this;
        }
        return $this->rVariables[ $i_stVarName ] = $tnv;
    }


    /**
     * @return list<?string> Returns an array containing the common prefix,
     *                       the matched key, and the remaining path,
     *                       or null if no match is found.
     */
    public function matchConstantPrefix( string $i_stPath ) : array {
        return static::matchPrefix( $this->rConstants, $i_stPath );
    }


    /**
     * @param string $i_stPath
     * @return list<?string> Returns an array containing the variable name
     *                       (or null if no match is found) and the
     *                       remaining path.
     */
    public function matchVariablePrefix( string $i_stPath ) : array {
        [ $stVarName, $stRest ] = static::extractVariableName( $i_stPath );
        if ( is_null( $stVarName ) ) {
            return [ null, null ];
        }
        if ( ! isset( $this->rVariables[ $stVarName ] ) ) {
            return [ null, null ];
        }
        return [ $stVarName, $stRest ];
    }


    public function prune() : void {
        $stKey = $this->findParentKey();
        if ( is_null( $stKey ) ) {
            return;
        }
        if ( str_starts_with( $stKey, '$' ) ) {
            # This is a variable node.
            unset( $this->parent->rVariables[ $stKey ] );
        } else {
            # This is a constant node.
            unset( $this->parent->rConstants[ $stKey ] );
        }
        $this->parent = null;
    }


    public function setValue( mixed $i_xValue, bool $i_bAllowOverwrite = false ) : void {
        if ( $i_bAllowOverwrite || is_null( $this->xValue ) ) {
            $this->xValue = static::asNotNode( $i_xValue );
            return;
        }
        throw new InvalidArgumentException( 'Node already has a value' );
    }


    /**
     * @param string $i_stPrefix
     * @param string $i_stOldKey
     * @param string $i_stNewRest The new key to be added after the prefix.
     * @param mixed $i_xNewValue The new value to be associated with the new key.
     * @return static Returns the child node associated with the new value.
     *
     * This method splits a constant link (e.g., "FooBar") to add a new
     * constant edge that shares a prefix (e.g., "FooQux") so the result
     * is a constant edge ("Foo") to a new node with two constant edges,
     * "Bar" and "Qux" to the old and new nodes respectively.
     *
     * This also handles the case where either the old key or the new
     * key matches the prefix.
     */
    public function splitConstant( string $i_stPrefix, string $i_stOldKey, string $i_stNewRest,
                                   mixed  $i_xNewValue ) : static {

        # There are three cases to consider:
        # 1. The old key is a prefix of the new key. (E.g., "FooBar" for "Foo")
        # 2. There is a common prefix that doesn't match either key. (E.g., "Foo" for "FooBar" and "FooQux").
        # 3. The new key is a prefix of the old key. (E.g., "Foo" for "FooBar")

        $stOldRest = substr( $i_stOldKey, strlen( $i_stPrefix ) );
        $tncOldConstant = $this->constantEx( $i_stOldKey );
        $i_xNewValue = static::asNotNode( $i_xNewValue );

        # Case 1: Old key is a prefix of the new key.
        # It's debatable whether this can occur, because whatever search
        # you performed should have returned the existing matching child
        # node rather than this one. But it may be useful to be able to
        # split unconditionally for some tree grooming operations.
        if ( '' === $stOldRest && '' !== $i_stNewRest ) {
            return $tncOldConstant->addConstant( $i_stNewRest, $i_xNewValue );
        }

        # No matter what, we need to remove the old key from the list of
        # constants, because we are going to add a new edge.
        $tncOldConstant->prune();

        # Case 2: New key is a prefix of the old key.
        if ( '' === $i_stNewRest ) {
            $tncNewConstant = $this->linkConstant( $i_stPrefix, $i_xNewValue );
            $tncNewConstant->linkConstant( $stOldRest, $tncOldConstant );
            return $tncNewConstant;
        }

        # Case 3: Common prefix.
        $tncSplit = $this->linkConstant( $i_stPrefix, null );
        $tncSplit->linkConstant( $stOldRest, $tncOldConstant );
        return $tncSplit->linkConstant( $i_stNewRest, $i_xNewValue );

    }


    public function unsetValue() : void {
        $this->xValue = null;
    }


    public function variable( string $i_stPath ) : ?static {
        /** @phpstan-ignore-next-line */
        return $this->rVariables[ $i_stPath ] ?? null;
    }


    public function variableEx( string $i_stPath ) : static {
        if ( isset( $this->rVariables[ $i_stPath ] ) ) {
            /** @phpstan-ignore-next-line */
            return $this->rVariables[ $i_stPath ];
        }
        throw new InvalidArgumentException( "Variable node at '{$i_stPath}' does not exist" );
    }


    /** @return iterable<string, static>
     * @noinspection PhpCastIsUnnecessaryInspection
     */
    public function variables() : iterable {
        foreach ( $this->rVariables as $stKey => $tn ) {
            yield strval( $stKey ) => static::cast( $tn );
        }
    }


}
