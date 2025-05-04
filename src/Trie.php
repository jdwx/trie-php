<?php


declare( strict_types = 1 );


namespace JDWX\Trie;


class Trie {


    protected const string VARIABLE_VALUE_REGEX = '/^([-a-zA-Z0-9_.]+)(.*)$/';

    protected TrieNode $tnRoot;


    public function __construct( private readonly bool $bAllowVariables = false ) {
        $this->tnRoot = new TrieNode();
    }


    /**
     *
     * @return list<?string>
     *
     * This function will probably need to be overloaded in a subclass
     * to deal with the variable values. For the base implementation,
     * it allows variable values to use alphanumeric characters, plus
     * underscore, period, and hyphen. That's pretty arbitrary.
     */
    public static function extractVariableValue( string $i_stPath ) : array {
        $rMatches = [];
        if ( ! preg_match( static::VARIABLE_VALUE_REGEX, $i_stPath, $rMatches ) ) {
            return [ null, $i_stPath ];
        }
        $stValue = $rMatches[ 1 ];
        $stPath = $rMatches[ 2 ];
        return [ $stValue, $stPath ];
    }


    public function add( string $i_stPath, mixed $i_xValue ) : void {
        $this->tnRoot->add( $i_stPath, $i_xValue, $this->bAllowVariables );
    }


    /** @param array<string,string>|null &$o_nrVariables */
    public function get( string $i_stPath, ?array &$o_nrVariables = null ) : mixed {
        if ( is_array( $o_nrVariables ) ) {
            $o_nrVariables = [];
        }
        return $this->getInner( $this->tnRoot, $i_stPath, $o_nrVariables );
    }


    /** @param array<string, string>|null &$o_nrVariables */
    protected function getInner( TrieNode $i_node, string $i_stPath, ?array &$o_nrVariables ) : mixed {
        $stPath = $i_stPath;
        $nodeMatch = $i_node->get( $stPath );
        if ( '' === $stPath ) {
            return $nodeMatch->xValue;
        }
        if ( ! $this->bAllowVariables || empty( $nodeMatch->rVariableChildren ) || ! is_array( $o_nrVariables ) ) {
            return null;
        }

        [ $stValue, $stPath ] = static::extractVariableValue( $stPath );
        if ( null === $stValue ) {
            return null;
        }

        if ( empty( $stPath ) ) {
            if ( 1 === count( $nodeMatch->rVariableChildren ) ) {
                $stVarName = array_keys( $nodeMatch->rVariableChildren )[ 0 ];
                $o_nrVariables[ $stVarName ] = $stValue;
                return $nodeMatch->rVariableChildren[ $stVarName ]->xValue;
            }
            $stKeys = join( ', ', array_keys( $nodeMatch->rVariableChildren ) );
            throw new \RuntimeException( "Variable substitution is ambiguous with: {$stKeys}" );
        }

        # There's more to the path after the variable.
        $rMatches = [];
        foreach ( $nodeMatch->rVariableChildren as $stVarName => $tnChild ) {
            $rVariables = [ $stVarName => $stValue ];
            $x = $this->getInner( $tnChild, $stPath, $rVariables );
            if ( null === $x ) {
                continue;
            }
            $rMatches[ $stVarName ] = [ $x, $rVariables ];
        }
        if ( 0 === count( $rMatches ) ) {
            return null;
        }
        if ( 1 === count( $rMatches ) ) {
            $rMatches = array_shift( $rMatches );
            $o_nrVariables = $rMatches[ 1 ];
            return $rMatches[ 0 ];
        }
        $stKeys = join( ', ', array_keys( $rMatches ) );
        throw new \RuntimeException( "Variable substitution is ambiguous on '{$stPath}' with: {$stKeys}" );
    }


}
