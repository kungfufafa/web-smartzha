<?php

defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('ci_where_in_values'))
{
    /**
     * Normalize values for Query Builder where_in/having_in.
     *
     * Accepts:
     * - array
     * - scalar (int/string)
     * - comma-separated string ("1,2,3")
     *
     * Returns a non-empty array when possible, otherwise empty array.
     *
     * @param mixed $values
     * @return array
     */
    function ci_where_in_values($values)
    {
        if (is_array($values))
        {
            return $values;
        }

        if ($values === NULL)
        {
            return array();
        }

        if (is_string($values))
        {
            $values = trim($values);
            if ($values === '')
            {
                return array();
            }

            if (strpos($values, ',') !== FALSE)
            {
                $parts = array_map('trim', explode(',', $values));
                return array_values(array_filter($parts, 'strlen'));
            }

            return array($values);
        }

        return array($values);
    }
}
