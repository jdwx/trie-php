<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


use ArrayAccess;
use InvalidArgumentException;


/**
 * @implements ArrayAccess<string, mixed>
 */
class Trie implements ArrayAccess {


    protected TrieNodeNavigator $tnRoot;


    /** @var array<string, string> */
    private array $rOffsetGetVariables = [];

    private ?string $nstOffsetGetRest = null;


    public function __construct( private readonly bool $bAllowVariables = false,
                                 private readonly bool $bAllowExtra = false ) {
        $this->tnRoot = new TrieNodeNavigator( null, null );
    }


    public function add( string $i_stPath, mixed $i_xValue ) : void {
        $this->tnRoot->add( $i_stPath, $i_xValue, $this->bAllowVariables, false );
    }


    /**
     * @param array<string,string>|null &$o_nrVariables
     * @phpstan-ignore parameterByRef.unusedType
     */
    public function get( string $i_stPath, ?array &$o_nrVariables = null, ?string &$o_nstRest = null ) : mixed {
        $o_nrVariables = [];
        $o_nstRest = $this->bAllowExtra ? '' : null;
        return $this->tnRoot->get( $i_stPath, $o_nrVariables, $this->bAllowVariables, $o_nstRest );
    }


    public function has( string $i_stPath, bool $i_bSubstituteVariables = false, bool $i_bAllowExtra = false ) : bool {
        return $this->tnRoot->has( $i_stPath, $this->bAllowVariables, $i_bSubstituteVariables, $i_bAllowExtra );
    }


    /** @return iterable<TrieMatch> */
    public function match( string $i_stMatch ) : iterable {
        return $this->tnRoot->match( $i_stMatch, $this->bAllowVariables, true, [] );
    }


    /**
     * @param string $offset
     * @return bool
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetExists( mixed $offset ) : bool {
        /** @phpstan-ignore-next-line */
        if ( ! is_string( $offset ) ) {
            throw new InvalidArgumentException( 'Trie keys must be strings.' );
        }
        return $this->has( $offset, $this->bAllowVariables, $this->bAllowExtra );
    }


    /**
     * @param string $offset
     * @return mixed
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetGet( mixed $offset ) : mixed {
        /** @phpstan-ignore-next-line */
        if ( ! is_string( $offset ) ) {
            throw new InvalidArgumentException( 'Trie keys must be strings.' );
        }
        $this->nstOffsetGetRest = $this->bAllowExtra ? '' : null;
        return $this->get( $offset, $this->rOffsetGetVariables, $this->nstOffsetGetRest );
    }


    /**
     * @param string $offset
     * @param mixed $value
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetSet( mixed $offset, mixed $value ) : void {
        /** @phpstan-ignore-next-line */
        if ( ! is_string( $offset ) ) {
            throw new InvalidArgumentException( 'Trie keys must be strings.' );
        }
        $this->set( $offset, $value );
    }


    /**
     * @param string $offset
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetUnset( mixed $offset ) : void {
        /** @phpstan-ignore-next-line */
        if ( ! is_string( $offset ) ) {
            throw new InvalidArgumentException( 'Trie keys must be strings.' );
        }
        $this->unset( $offset );
    }


    public function rest( ?string $i_nstDefault = null ) : ?string {
        return $this->nstOffsetGetRest ?? $i_nstDefault;
    }


    public function restEx( ?string $i_nstDefault = null ) : string {
        $nst = $this->rest( $i_nstDefault );
        if ( is_string( $nst ) ) {
            return $nst;
        }
        throw new \RuntimeException( 'No rest value available' );
    }


    public function set( string $i_stPath, mixed $i_xValue ) : void {
        $this->tnRoot->set( $i_stPath, $i_xValue, $this->bAllowVariables, true );
    }


    public function unset( string $i_stPath, bool $i_bPrune = false ) : void {
        $this->tnRoot->unset( $i_stPath, $this->bAllowVariables, $i_bPrune );
    }


    /**
     * @param string $i_stName The variable name to retrieve.
     * @param string|null $i_nstDefault The value to return if the variable is not set.
     * @return string|null The value found (or the default value).
     */
    public function var( string $i_stName, ?string $i_nstDefault = null ) : ?string {
        return $this->rOffsetGetVariables[ $i_stName ] ?? $i_nstDefault;
    }


    /**
     * @param string $i_stName The variable name to retrieve.
     * @param string|null $i_nstDefault The value to return if the variable
     *                                  is not set.
     * @return string The value found (or the default value, if not null).
     * @throws InvalidArgumentException If the variable is not set and no
     *                                  default value is provided.
     */
    public function varEx( string $i_stName, ?string $i_nstDefault = null ) : string {
        $nst = $this->var( $i_stName, $i_nstDefault );
        if ( is_string( $nst ) ) {
            return $nst;
        }
        throw new InvalidArgumentException( "Variable '{$i_stName}' is not set." );
    }


    /**
     * @return array<string, string> The array of variables found in the last
     *                               offsetGet() access.
     */
    public function variables() : array {
        return $this->rOffsetGetVariables;
    }


}
