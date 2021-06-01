<?php

namespace App\Utils;

/**
 * Sanitize, filter and type convert values, for class internal use
 */
trait ValueFilter {
    
    /**
     * Gets a value from an assoc array and makes sanity checks
     */
    protected function valFromMap($array, $key, $default = null)
    {
        if (!is_array($array)) {
            throw new \ErrorException("Provided map is not valid array.");
        }

        if (!isset($array[$key])) {
            return $default;
        }

        $val = $array[$key];

        return is_string($val) ? trim($val) : $val;
    }

    /**
     * Make sures val is a subset of allowed, gets value of first index if not
     * This is case sensitive
     */
    protected function valExpectsIn($val, $allowed = [])
    {
        if (!in_array($val, $allowed)) {
            return $allowed[0];
        }
        return $val;
    }

}