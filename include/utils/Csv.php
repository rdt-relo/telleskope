<?php

class Csv
{
    /**
     * This function is to allow parsing for multi-response questions in the downloaded CSV
     * For checkboxes, we can have multiple responses and we show them in a single cell by delimiting by ,
     * So for a checkbox, if user selects option1, option2 then earlier cell value was <option1,option2>
     * To parse option1 and option2 from this, we can use the delimiter ,
     * But what if option1 or option2 had a comma in them?
     * Then we can't use the delimiter ,
     * So that's why now we are adding double quotes to values with comma
     * These double quotes help in parsing and identifying if comma is a delimiter OR just normal char
     * GetCell(['a', 'b']) -> a,b (Here comma is delimiter)
     * GetCell(['a,b']) -> "a,b" (Here comma is normal char)
     * We are able to parse this due to double quotes, if comma is inside double quotes its a normal char else its a delimiter
     */
    public static function GetCell($input): ?string
    {
        if (!is_array($input)) {
            return $input;
        }

        if (count($input) === 0) {
            return '';
        }

        if (count($input) === 1) {
            if (strpos($input[0], ',') === false) {
                return $input[0];
            }
        }

        $file = fopen('php://temp', 'r+');
        fputcsv($file, $input);
        rewind($file);
        $csv = fread($file, 1048576);
        fclose($file);
        return rtrim($csv, "\n");
    }

    /**
     * This function is to allow parsing for multi-response questions in the downloaded CSV
     *
     * ParseCell('a,b') -> ['a', 'b']
     * ParseCell("a,b") -> 'a,b'
     * ParseCell(GetCell(['a', 'b'])) -> ['a', 'b']
     * ParseCell(GetCell(['a,b'])) -> 'a,b'
     */
    public static function ParseCell(string $cell)
    {
        if ($cell === '') {
            return '';
        }

        $parts = str_getcsv($cell);
        if (count($parts) === 1) {
            return $parts[0];
        }
        return $parts;
    }

    /**
     * @param $csvfile Filename. File needs to be CSV with UTF-8 encoding
     * @return array
     * @throws Exception
     */
    public static function ParseFile(string $csvfile): array
    {
        $csv = array();
        $rowcount = 0;

        if (filesize($csvfile) > 5242880) {
            throw new Exception("CSV Reader Error: File size is greater than allowed maximum of 5MB");
        }

        if (($handle = fopen($csvfile, "r")) !== FALSE) {
            $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 2000;
            $header = fgetcsv($handle, $max_line_length);
            $header = array_map('strtolower', $header); // convert the header to lowercase
            $header = array_map('trim', $header); // trim white spaces from header
            $file_encoding = mb_detect_encoding($header[0], mb_detect_order(), TRUE);
            if ($file_encoding != 'UTF-8' && $file_encoding != 'ASCII') {
                fclose($handle);
                throw new Exception('CSV Reader Error: CSV file needs to be UTF-8 (preferred) or ASCII encoded');
            }
            $header_colcount = count($header);
            while (($row = fgetcsv($handle, $max_line_length)) !== FALSE) {
                $row_colcount = count($row);
                if ($row_colcount == $header_colcount) {
                    // Clean all the inputs for extra white spaces
                    $row = array_map('trim', $row); // trim white spaces from row

                    $entry = array_combine($header, $row);
                    $csv[] = $entry;
                } else {
                    fclose($handle);
                    throw new Exception("CSV Reader Error: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                }
                $rowcount++;
            }
            //echo "Totally $rowcount rows found\n";
            fclose($handle);
        } else {
            throw new Exception("CSV Reader Error: Could not read the CSV file");
        }

        return $csv;
    }
}
