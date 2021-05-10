<?php

namespace Emsifa\Laci;

class JsonUtil 
{
    public static function load( $filename ) {
        $contents = @file_get_contents( $filename );
        if ( $contents === false ) {
            return false;
        }
        return json_decode(self::stripComments($contents),true);
    }

    protected static function stripComments( $str ) {
        return preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $str);
    }
}