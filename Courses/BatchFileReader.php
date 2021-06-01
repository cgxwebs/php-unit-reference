<?php

namespace Courses;

use Akeneo\Component\SpreadsheetParser\SpreadsheetParser;
use App\Utils\ValueFilter;
use DateTime;
use Exception;
use PHPExcel_IOFactory;

class BatchFileReader {

    use ValueFilter;

    // in Mb
    const FILESIZE_LIMIT = 10;

    private $file = '';

    private $type = '';

    private $content;

    /**
     * @param array $file A single value from $_FILE
     */
    public static function loadFromUpload($file)
    {
        // Redacted
    }

    public function __construct($file, $type)
    {
        $this->file = $file;
        $this->type = $type;

        if (!file_exists($this->file)) {
            throw new \InvalidArgumentException("File does not exists");
        }

        if (!in_array($type, ['xls', 'xlsx', 'csv'])) {
            $this->deleteFile();
            throw new \InvalidArgumentException("File type invalid, allowed are .xls, .xlsx and .csv");
        }

        if ($this->type !== 'xls') {
            $this->content = $this->readFile();
        } else {
            $this->content = $this->readFileAsXls();
        }
        $this->deleteFile();
    }

    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns name/index pair of heading indices from content
     * False if error
     */
    public function getHeadingIndices($headings = [])
    {
        $first = 0;

        if (!isset($this->content[$first])) {
            throw new Exception("Headings are incomplete and/or incorrectly labeled");
        }

        if (count($this->content[$first]) < count($headings)) {
            throw new Exception("Headings are incomplete and/or incorrectly labeled");
        }

        $match_heading = [];
        foreach($headings as $h) {
            $match_heading[$h] = strtoupper(trim(preg_replace('/\s/', '', $h)));
        }

        // This compares case insensitive headings disregarding whitespace
        $indices = [];
        $unmatched = count($headings);
        $cheads = $this->content[$first];
        foreach($match_heading as $h => $m) {
            for ($i = 0; $i < count($cheads); $i++) {
                $c = strtoupper(trim(preg_replace('/\s/', '', $cheads[$i])));
                if ($c === $m) {
                    $indices[$h] = $i;
                    $unmatched--;
                    break;
                }
            }
        }

        if ($unmatched !== 0) {
            throw new Exception("Headings are incomplete and/or incorrectly labeled");
        }
        return $indices;
    }

    public function getSortedContentWithDateTime($headings, $dtcols = []) 
    {
        $content = [];
        for ($i = 1; $i < count($this->content); $i++) {
            $content[] = $this->getSortedRowWithDateTime($i, $headings, $dtcols);
        }
        return $content;
    }

    /**
     * Return a row filtered with heading indices
     * and converts datetime columns into objects, specified with $dtrows (as keys of headings)
     */
    public function getSortedRowWithDateTime($row_idx, $headings = [], $dtcols = [])
    {
        if (!isset($this->content[$row_idx])) {
            throw new \InvalidArgumentException("Cannot find row " . ($row_idx + 1));
        }

        $row = $this->content[$row_idx];
        
        $sorted = [];
        foreach($headings as $key => $idx) {
            $sorted[$key] = $this->valFromMap($row, $idx);
            if (in_array($key, $dtcols)) {
                $sorted[$key] = $this->convertToDateTime($sorted[$key]);
            }
        }

        return $sorted;
    }

    private function convertToDateTime($string)
    {
        if ($string instanceof DateTime) {
            return $string;
        }

        $allowed_formats = [
            'm/d/y',
            'm/d/Y',
            'n/d/y',
            'n/d/Y',
            'm/j/y',
            'm/j/Y',
            'n/j/y',
            'n/j/Y',
            'm/d/Y H:i:s',
            'Y-m-d H:i:s',
            'Y-m-d',
            'Y-n-d',
            'Y-m-D',
            'Y-n-D',
        ];

        foreach($allowed_formats as $f) {
            $datetime = DateTime::createFromFormat($f, $string);
            if ($datetime && $datetime->format($f) === $string) {
                return $datetime;
            }
        }

        return '';
    }

    private function readFile()
    {
        if ($this->type !== 'xls') {
            $workbook = SpreadsheetParser::open($this->file);
            $content = [];
            foreach ($workbook->createRowIterator(0) as $values) {
                $content[] = $values;
            }
            return $content;
        }
        return null;
    }

    private function readFileAsXls()
    {
        if ($this->type === 'xls') {
            $objPHPExcel = PHPExcel_IOFactory::load($this->file);

            $objPHPExcel->setActiveSheetIndex(0);
            $res = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

            // Normalize
            $content = [];
            $i = $j = 0;
            foreach($res as $row) {
                $content[$i] = [];
                $j = 0;
                foreach($row as $col) {
                    $content[$i][$j] = $col;
                    $j++;
                } 
                $i++;
            }

            return $content;
        }
        return null;
    }

    private function deleteFile()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

}