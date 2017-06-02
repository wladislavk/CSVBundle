<?php
namespace VKR\CSVBundle\Services;

use VKR\CSVBundle\Exception\MalformedCSVFieldsException;
use VKR\CSVBundle\Exception\MalformedCSVObjectException;

/**
 * Class CSVCreator
 * Creates a CSV from any table-based data
 */
class CSVCreator
{
    /**
     * Takes any iterable collection of data and parses it to CSV using a ruleset
     *
     * @param mixed $dataObject Iterable collection of entities
     * @param string[] $fields Fields to save to CSV. Every row of $dataObject has to implement a
     * getField() method on each entry in $fields
     * @param string[] $fieldIndexes The first row of CSV file should contain field names. If we want
     * those names to be different from actual field names in the entity, this array should be
     * populated. The array must be either empty or of same size as $fields
     * @param string $separator
     * @param string $separatorReplacement
     * @param string $dateFormat If a record is a DateTime object, it will be converted to string
     * using this format
     * @param string $filler Used to fill empty values
     * @return string
     */
    public function parseDataToCSV(
        $dataObject,
        array $fields,
        array $fieldIndexes = [],
        $separator = '',
        $separatorReplacement = '',
        $dateFormat='',
        $filler = ''
    ) {
        $defaultSeparator = ',';
        $defaultReplacement = ' ';
        if (!$separator) {
            $separator = $defaultSeparator;
        }
        if (!$separatorReplacement) {
            $separatorReplacement = $defaultReplacement;
        }
        $this->checkDataObject($dataObject);
        $this->checkFields($fields, $fieldIndexes, $separator);
        $data = $this->setFirstRow($fields, $fieldIndexes, $separator);
        foreach ($dataObject as $row) {
            $dataRow = [];
            foreach ($fields as $field) {
                $dataValue = $this->getDataValue($row, $field, $dateFormat, $filler);
                $dataRow[] = str_replace($separator, $separatorReplacement, $dataValue);
            }
            $data .= implode($separator, $dataRow) . "\n";
        }
        return $data;
    }

    /**
     * Sets CSV headers, including file name
     *
     * @param string $filename
     * @return string[]
     */
    public function setHeaders($filename='')
    {
        if (!$filename) {
            $filename = time();
        }
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'. $filename .'.csv"',
        ];
        return $headers;
    }

    private function checkDataObject($dataObject)
    {
        if (!is_array($dataObject) && $dataObject instanceof \Traversable !== true) {
            throw new MalformedCSVObjectException();
        }
    }

    /**
     * @param string[] $fields
     * @param string[] $fieldIndexes
     * @param string $separator
     * @throws MalformedCSVFieldsException
     */
    private function checkFields(array $fields, array $fieldIndexes, $separator)
    {
        foreach ($fields as $field) {
            try {
                $fieldStr = '' . $field;
            } catch (\Exception $e) {
                throw new MalformedCSVFieldsException(
                    'Every element of $fields must have a string representation'
                );
            }
            if (strstr($field, $separator) && !sizeof($fieldIndexes)) {
                throw new MalformedCSVFieldsException(
                    '$fields elements cannot contain "' . $separator . '" symbol'
                );
            }
        }
        if (sizeof($fieldIndexes)) {
            if (sizeof($fieldIndexes) !== sizeof($fields)) {
                throw new MalformedCSVFieldsException(
                    '$fieldIndexes must be empty or have same size as $fields'
                );
            }
            foreach ($fieldIndexes as $field) {
                try {
                    $fieldStr = '' . $field;
                } catch (\Exception $e) {
                    throw new MalformedCSVFieldsException(
                        'Every element of $fieldIndexes must have a string representation'
                    );
                }
                if (strstr($field, $separator)) {
                    throw new MalformedCSVFieldsException(
                        '$fields elements cannot contain "' . $separator . '" symbol'
                    );
                }
            }
        }
    }

    /**
     * @param array $fields
     * @param array $fieldIndexes
     * @param string $separator
     * @return string
     */
    private function setFirstRow(array $fields, array $fieldIndexes, $separator)
    {
        if (sizeof($fieldIndexes)) {
            return implode($separator, $fieldIndexes) . "\n";
        }
        return implode($separator, $fields) . "\n";
    }

    /**
     * @param mixed $row
     * @param string $field
     * @param string $dateFormat
     * @param string $filler
     * @return string
     */
    private function getDataValue($row, $field, $dateFormat, $filler)
    {
        $dataValue = $filler;
        if (is_object($row)) {
            $methodName = 'get' . ucwords($field);
            if (method_exists($row, $methodName) && $row->{$methodName}() !== null) {
                $dataValue = $row->{$methodName}();
            }
        }
        if (is_array($row)) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $dataValue = $row[$field];
            }
        }
        if ($dataValue instanceof \DateTime) {
            return $dataValue->format($dateFormat);
        }
        if (is_bool($dataValue)) {
            $dataValue = intval($dataValue);
        }
        // force __toString() on value to make any exceptions point to this line
        $dataValue = '' . $dataValue;
        return $dataValue;
    }
}
