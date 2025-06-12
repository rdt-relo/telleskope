<?php

/**
 * Utity function for array
 */
class Arr
{

    /**
     * Set an array item to a given value using "dot" notation. If no key is given to the method, the entire array will be replaced.
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public static function Set(array &$array, string $key, mixed $value): array
    {
        if (empty($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Get an item from an array using "dot" notation.
     * @param array $array
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function Get(array $array, string $key, mixed $default = null): mixed
    {
        if (empty($key)) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $key_part) {
            if (is_array($array) && array_key_exists($key_part, $array)) {
                $array = $array[$key_part];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Remove array item from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  string  $key
     * @return void
     */
    public static function Forget(array &$array, string $key)
    {
        $original = &$array;

        if (empty($key)) {
            return;
        }

        // if the exact key exists in the top-level, remove it
        if (array_key_exists($key, $array)) {
            unset($array[$key]);
            return;
        }

        $parts = explode('.', $key);

        while (count($parts) > 1) {
            $part = array_shift($parts);
            if (isset($array[$part]) && is_array($array[$part])) {
                $array = &$array[$part];
            } else {
                continue;
            }
        }
        unset($array[array_shift($parts)]);
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function Pull(array &$array, string $key, mixed $default = null): mixed
    {
        $value = self::Get($array, $key, $default);

        self::Forget($array, $key);

        return $value;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     * @param array $array
     * @param string $prepend
     * @return array
     */
    public static function Dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, self::Dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     * @param array $array
     * @return array
     */
    public static function Undot(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            self::Set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Sorts multidimensional array by multiple fields
     * Call style $sorted = Arr::OrderBy($data, 'key', SORT_DESC,'key2 if any', SORT_DESC or SORT_ASC);
     * @return array|null
     */
    public static function OrderBy()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        array_multisort(...$args);
        return array_pop($args);
    }

    public static function GroupBy(array $items, string $group_by_column): array
    {
        $groups = [];
        foreach ($items as $item) {
            $groups[$item[$group_by_column]] ??= [];
            $groups[$item[$group_by_column]][] = $item;
        }

        return $groups;
    }

    public static function SumColumnValues(array $data, string $columnName): float|int
    {
        if (empty($data)) {
            return 0; // Handle empty array gracefully
        }
        return array_sum(array_column($data, $columnName));
    }


    /**
     * This function does the opposite of GroupBy.
     * It uses the to_column to introduce a column that will contain the group key
     * e.g.
     * $a = [];
     * $a['apple'] = [['color'=>'red','unit'=>'kgs'],['color'=>'green','unit'=>'kgs']];
     * $a['bananas'] = [['color'=>'red','unit'=>'count'],['color'=>'green','unit'=>'count']];
     * echo(json_encode(UngroupTo($a,'fruit'));
     * prints [{"fruit":"apple","color":"red","unit":"kgs"},{"fruit":"apple","color":"green","unit":"kgs"},{"fruit":"bananas","color":"red","unit":"count"},{"fruit":"bananas","color":"green","unit":"count"}]
     *
     * @param array $items
     * @param string $to_column
     * @return array
     */
    public static function UngroupTo(array $items, string $to_column) : array
    {
        if (empty($to_column))
            return $items;

        $rows = [];
        foreach ($items as $k => $v) {
            //if (!is_array($v) || self::IsAssoc($v)) continue;
            if (!is_array($v)) continue;
            foreach ($v as $v2) {
                if (!is_array($v2)) continue;
                $rows[] = [$to_column => $k, ...$v2];
            }
        }
        return $rows ?: $items ;
    }

    /**
     * This method searches a column called $search_col_name in a multidimensional array called $haystack for a match with
     * $needle. If a match is found the corresponding value of $return_col_name is returned. Default return value is empty
     * string
     * @param array $haystack
     * @param string $needle
     * @param string $search_col_name
     * @param string $return_col_name
     * @return mixed
     */
    public static function SearchColumnReturnColumnVal(array $haystack, string $needle, string $search_col_name, string $return_col_name): mixed
    {
        $retVal = '';
        $haystack = array_values($haystack);
        if (!empty($haystack) && ($k = array_search($needle, array_column($haystack, $search_col_name))) !== false) {
            $retVal = $haystack[$k][$return_col_name];
        }
        return $retVal;
    }

    /**
     * This method searches a column called $search_col_name in a multidimensional array called $haystack for a match with
     * $needle. If a match is found the corresponding row returned. Default return value is empty string
     * @param array $haystack
     * @param string $needle
     * @param string $search_col_name
     * @return array
     */
    public static function SearchColumnReturnRow(array $haystack, string $needle, string $search_col_name): array
    {
        $retVal = array();
        $haystack = array_values($haystack);
        if (!empty($haystack) && ($k = array_search($needle, array_column($haystack, $search_col_name))) !== false) {
            $retVal = $haystack[$k];
        }
        return $retVal;
    }

    /**
     * Function to minify the configuration by comparing it to the template and only keeping the values that are different
     * from the template.
     * @param array $array Array to be minified
     * @param array $template
     * @return array
     */
    public static function Minify(array $array, array $template): array
    {
        $retVal = array();

        foreach ($array as $k => $v) {
            if (array_key_exists($k, $template)) {
                if (is_array($v)) {
                    $aRecursiveDiff = self::Minify($v, $template[$k]);
                    if (count($aRecursiveDiff)) {
                        $retVal[$k] = $aRecursiveDiff;
                    }
                } else {
                    if ($v != $template[$k]) {
                        $retVal[$k] = $v;
                    }
                }
            }
        }
        return $retVal;
    }

    /**
     * Does the opposite of Minify by adding all the missing values from the provided template
     * @param array $array array to be unminified
     * @param array $template
     * @return array
     */
    public static function Unminify(array $array, array $template): array
    {
        return array_replace_recursive($template, $array);
    }

    /**
     * Returns true if an array is associative.
     * @param array $arr
     * @return bool
     */
        public static function IsAssoc(array $arr): bool
        {
            return !array_is_list($arr);
        }

    /**
     * Returns true if array is a list
     * @param $array
     * @return bool
     */
    public static function IsList($array)
    {
        return array_is_list($array);
    }

    /**
     * Converts the json value string to an array. For class members use the val_json2Array method.
     * @param string|null $what
     * @param bool $add_backslashes; set to true when handing $_POST or $_GET variable
     * @return mixed null if the value cannot be converted otherwise an array
     */
    public static function Json2Array(?string $what, bool $add_backslashes = false): mixed
    {
        $what = $what ?? '';
        // Convert backslashes to double backslashes
        if ($add_backslashes) {
            $what = str_replace('\\', '\\\\', $what);
        }
        return json_decode($what, true);
    }

    /**
     * Performs a natural language join for variables using provided conjunction
     * @param array $list
     * @param string $conjunction
     * @param int $max
     * @param string $more_label
     * @return string
     */
    public static function NaturalLanguageJoin(array $list, string $conjunction = 'and', int $max = 0, string $more_label = ' more'): string
    {
        if ($max > 0) {
            $list_updated = array_splice($list, 0, $max);
            if ($list) { // If there are more items left then add them as more label
                $list_updated[] = count($list) . $more_label; // Add last item as more cound
            }
        } else {
            $list_updated = $list;
        }

        $last = array_pop($list_updated);
        if ($list_updated) {
            return implode(', ', $list_updated) . ' ' . $conjunction . ' ' . $last;
        }
        return '' . $last;
    }

    public static function AsHtmlList (array $array_list, bool $ordered = false, string $css_class_for_list = '') : string
    {
        $html_list = '';
        if ($array_list) {
            $list_type = $ordered ? 'ol' : 'ul';
            $list_class = $css_class_for_list ? ' class="' . $css_class_for_list . '"' : '';
            $html_list = "<{$list_type}{$list_class}>";
            foreach ($array_list as $item) {
                $html_list .= "<li>{$item}</li>";
            }
            $html_list .= "</$list_type>";
        }
        return $html_list;
    }

    /**
     * Search a multidimensional array and return all rows with a string match. Search is case insensitive.
     * @param array $array The multidimensional array to search.
     * @param string $search_term The string to search for.
     * @param string|null $column_key The key of the column to search within, if you want to restrict the search to a specific column.
     * @return array
     */
    public static function SearchMultiArray (array $array, string $search_term, string $column_key = null): array
    {
        $matches = [];

        if (empty($search_term))
            return $array;

        foreach ($array as $row) {
            if ($column_key === null) {
                // Search all values in the row
                foreach ($row as $value) {
                    if (stripos($value ?? '', $search_term) !== false) {
                        $matches[] = $row;
                        break; // Exit inner loop if a match is found
                    }
                }
            } else {
                // Search only the specified column
                if (stripos($row[$column_key], $search_term) !== false) {
                    $matches[] = $row;
                }
            }
        }

        return $matches;
    }

    /**
     * This method extracts all the columns that match names specfied in columns and returns a multidimensional array
     * e.g.
     * $data = [ ['id' => 1, 'name' => 'Alice', 'age' => 25], ['id' => 2, 'name' => 'Bob', 'age' => 30] ];
     * $columns = ['name', 'age'];
     * $extracted_data = Arr::KeepColumns($data, $columns);
     * extracted_data is [ ['name' => Alice, 'age' => 25] ,  ['name' => Bob, 'age' => 30]]
     *
     * @param array $array input multidimensional array
     * @param array $columns array with key names
     * @return array
     */
    public static function KeepColumns (array $array, array $columns): array
    {
        $result = [];
        foreach ($array as $row) {
            $result[] = array_intersect_key($row, array_flip($columns));
        }
        return $result;
    }

    /**
     * This method reduces multi-dimensional array by unique column values.
     * ***CAUTION*** Use only where entire row of multi-dimensional array is considered duplicate, if not only the first
     * value is returned
     * @param array $array
     * @param string $column name of column on which unique will be applied.
     * @param int $sort_flags
     *      SORT_REGULAR - compare items normally (don't change types) ... default value
     *      SORT_NUMERIC - compare items numerically
     *      SORT_STRING - compare items as strings
     *      SORT_LOCALE_STRING - compare items as strings, based on the current locale
     * @return array
     */
    public static function Unique(array $array, string $column, int $sort_flags = SORT_REGULAR): array
    {
        return array_intersect_key($array, array_unique(array_column($array, $column), $sort_flags));
    }

    /**
     * This function sorts an Array based on Order for a column provided in order array
     * @param array $data
     * @param array $order an array of integers
     * @param string $column a column that has integers
     * @return array
     */
    public static function SortByOrder(array $data, array $order, string $column)
    {
        // Validate input
        if (empty($order) || empty($column)) {
            return $data;
        }

        // Create a lookup table for order positions
        $order = Arr::IntValues($order);
        $orderLookup = array_flip($order);

        // Custom comparison function
        $compareFn = function($a, $b) use ($orderLookup, $column) {
            // Ensure both elements have the specified column
            if (!isset($a[$column]) || !isset($b[$column])) {
                throw new RuntimeException('Missing column in data');
            }
            $a_col = $a[$column];
            $b_col = $b[$column];
            // Compare order positions based on lookup table
            $posA = isset($orderLookup[$a_col]) ? $orderLookup[$a_col] : PHP_INT_MAX;
            $posB = isset($orderLookup[$b_col]) ? $orderLookup[$b_col] : PHP_INT_MAX;

            if ($posA == $posB)
                return 0;

            return ($posA < $posB) ? -1 : 1;
        };

        // Use usort to sort the data array
        usort($data, $compareFn);

        // Return the sorted data
        return $data;
    }

    /**
     * Returns values where $column value matches the entries in $filter.
     * @param array $data
     * @param array $filter
     * @param string $column
     * @return array
     */
    public static function KeepRowsByFilter(array $data, array $filter, string $column)
    {
        // Validate input
        if (empty($filter) || empty($column)) {
            return $data;
        }

        $is_assoc = self::IsAssoc($data);

        foreach ($data as $k => $v) {
            if (!in_array($v[$column], $filter)) {
                unset($data[$k]);
            }
        }

        // Return the filtered data
        return $is_assoc ? $data : array_values($data);
    }

    public static function IntValues (array $input) : array
    {
        return array_map('intval', $input);
    }

    /**
     * Removes all elements that have a matching value, returns the update array.
     * @param array $input
     * @return array
     */
    public static function RemoveByValue (array $input, string $val) : array
    {
        return array_filter($input, function($v) use ($val){
            return $v != $val;
        });
    }

    public static function SetDeepestValue (array &$target, $val)
    {
        foreach ($target as $k => &$v) {
            if (is_array($v)) {
                self::SetDeepestValue($v, $val);
            } else {
                $v = $val;
            }
        }
    }
}
