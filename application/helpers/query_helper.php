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
     * IMPORTANT: This function may return an empty array. You MUST check
     * for empty before passing to where_in() to avoid SQL syntax errors.
     *
     * Recommended usage:
     *   $ids = ci_where_in_values($data);
     *   if (empty($ids)) {
     *       return []; // or appropriate empty response
     *   }
     *   $this->db->where_in('id', $ids);
     *
     * Or use the safe wrapper:
     *   if ( ! safe_where_in($this->db, 'id', $data)) {
     *       return []; // No values to filter
     *   }
     *
     * @param mixed $values Raw input (array, scalar, or comma-separated string)
     * @return array Normalized array (may be empty!)
     */
    function ci_where_in_values($values)
    {
        if (is_array($values))
        {
            return array_values(array_filter($values, function($v) {
                return $v !== NULL && $v !== '';
            }));
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

if ( ! function_exists('safe_where_in'))
{
    /**
     * Safely apply where_in condition, handling empty values gracefully.
     *
     * This is the RECOMMENDED way to use where_in with dynamic values.
     * It prevents SQL syntax errors from empty arrays and provides
     * predictable behavior.
     *
     * Behavior:
     * - If values normalize to non-empty: applies where_in, returns TRUE
     * - If values normalize to empty: adds WHERE 1=0 (matches nothing), returns FALSE
     *
     * Usage:
     *   // Simple usage - query will match nothing if $ids is empty
     *   safe_where_in($this->db, 'id', $ids);
     *   $result = $this->db->get('table')->result();
     *
     *   // With early return pattern
     *   if ( ! safe_where_in($this->db, 'id', $ids)) {
     *       return []; // Skip query entirely
     *   }
     *   $result = $this->db->get('table')->result();
     *
     * @param CI_DB_query_builder $db    Database query builder instance
     * @param string              $key   Column name for the IN clause
     * @param mixed               $values Values to filter (array, scalar, or CSV string)
     * @param bool                $not   If TRUE, uses where_not_in instead
     * @return bool TRUE if values were applied, FALSE if empty (added 1=0 condition)
     */
    function safe_where_in($db, $key, $values, $not = FALSE)
    {
        $normalized = ci_where_in_values($values);

        if (empty($normalized))
        {
            $db->where('1=0', NULL, FALSE);
            return FALSE;
        }

        if ($not)
        {
            $db->where_not_in($key, $normalized);
        }
        else
        {
            $db->where_in($key, $normalized);
        }

        return TRUE;
    }
}

if ( ! function_exists('safe_or_where_in'))
{
    /**
     * Safely apply or_where_in condition, handling empty values gracefully.
     *
     * Same as safe_where_in but uses OR instead of AND.
     *
     * @param CI_DB_query_builder $db    Database query builder instance
     * @param string              $key   Column name for the IN clause
     * @param mixed               $values Values to filter
     * @param bool                $not   If TRUE, uses or_where_not_in instead
     * @return bool TRUE if values were applied, FALSE if empty
     */
    function safe_or_where_in($db, $key, $values, $not = FALSE)
    {
        $normalized = ci_where_in_values($values);

        if (empty($normalized))
        {
            $db->or_where('1=0', NULL, FALSE);
            return FALSE;
        }

        if ($not)
        {
            $db->or_where_not_in($key, $normalized);
        }
        else
        {
            $db->or_where_in($key, $normalized);
        }

        return TRUE;
    }
}

if ( ! function_exists('has_where_in_values'))
{
    /**
     * Check if values will produce a non-empty where_in clause.
     *
     * Use this when you need to check BEFORE building your query,
     * for example to decide whether to run a query at all.
     *
     * Usage:
     *   if ( ! has_where_in_values($ids)) {
     *       return []; // No point running query
     *   }
     *   // Build and run query...
     *
     * @param mixed $values Values to check
     * @return bool TRUE if values will produce non-empty array
     */
    function has_where_in_values($values)
    {
        return ! empty(ci_where_in_values($values));
    }
}
