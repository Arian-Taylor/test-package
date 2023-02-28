<?php
namespace AT\Utilities;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportExcelService {

    const DICTIONARY = [];

    /**
     * Private methode to format datas of export
     * @param [] $key_data, array collection ex: [["id": 1, "firstname": "John"], ["id": 2, "firstname": "Janne"]]
     * @param [] $key_labels, ex: ["Id" => "id", "Nom et Prénom" => "lastname_firstname"]
     * @param string $onglet_name
     * 
     * @return []
     */
    public function formatExport(
        $key_data, 
        $key_labels, 
        $onglet_name
    ){
        $result = [
            'onglet' => '',
            'table_data' => []
        ] ;

        $table_data = [] ;

        $table_data[$onglet_name] = [];

        $ths = [] ;
        $tds = [] ;

        foreach ($key_labels as $td => $th) {
            $ths[] = $th ;
            $tds[] = $td ;
        }

        $table_data[$onglet_name][] = $ths ; // Set th table

        foreach ($key_data as $data) {

            $tr = [] ;
            foreach ($tds as $td) {
                $tr[] = $this->parseTd($data, $td) ;
            }

            $table_data[$onglet_name][] = $tr ; // add an td(s) table
        }

        $result['onglet'] = $onglet_name ;
        $result['table_data'] = $table_data ;

        return $result;
    }

    /**
     * Private methode to format and render content of each column
     * @param [] $data
     * @param string $td, ex : "id", "firstname", "firstname_lastname", "created_at"
     * 
     * @return string
     */
    private function parseTd($data, $td)
    {
        $child = get_called_class();

        $opt = null;
        $value = $data[$td];
        if ($value && $value === "undefined") {
            return null;
        }

        if (
            isset($child) &&
            $child &&
            $child::DICTIONARY !== null &&
            isset($child::DICTIONARY[$td])
        ) {
            $opt = $child::DICTIONARY[$td];
        }
        if (
            $opt && 
            is_array($opt)
        ) {
            if (isset($opt[$value])) {
                $value = $opt[$value];
            }else {
                $value = null;
            }
        }
        return $value;
    }

    /**
     * Private methode to create the spreadSheet
     * @param [] $export, the result of $this->formatExport
     * @param [] $options
     * @param Spreadsheet $spreadsheet
     * @param int $index
     * 
     * @return Spreadsheet
     */
    public function setupSpreadSheet($export, $options = [], $spreadsheet = null, $index = 0){

        if (!$spreadsheet) {
            $spreadsheet = new Spreadsheet();
        }

        /* Options */
        $superHead = $this->get($options, "superHead", true);
        $superHeadSize = $this->get($options, "superHeadSize", 12);
        $superHeadColor = $this->get($options, "superHeadColor", "000000");
        $superHeadBgColor = $this->get($options, "superHeadBgColor", "ffffff");

        $headSize = $this->get($options, "headSize", 9);
        $headColor = $this->get($options, "headColor", "ffffff");
        $headBgColor = $this->get($options, "headBgColor", "003781");

        $contentSize = $this->get($options, "contentSize", 9);
        /* Options */

        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($index);

        $spreadsheet->getActiveSheet()->setTitle($export['onglet']);

        $headColumn = 1;

        $all_data = $export["table_data"];

        $spreadsheet->getActiveSheet()->getRowDimension('2')->setRowHeight(30);

        foreach ($all_data as $onglet => $data) {
            $innerColumn = 0;
            if(isset($data[0])){
                $heads = $data[0];
                $lines = [];
                foreach ($data as $lineIndex => $line) {
                    if($lineIndex){
                        $lines[] = $line;
                    }
                }

                $index_col = 1;

                if ($superHead) {
                    $col_value = Coordinate::stringFromColumnIndex($headColumn);
                    $string_index_col = strval($index_col);
                    $col_start = $col_value.$string_index_col;

                    $col_value = Coordinate::stringFromColumnIndex(count($heads) + $headColumn - 1);
                    $col_end = $col_value.$string_index_col;

                    $headLine = "$col_start:$col_end";

                    $spreadsheet->getActiveSheet()
                        ->mergeCells($headLine);

                    $superHead = $spreadsheet
                        ->getActiveSheet()
                        ->getStyle($headLine);

                    $superHead
                        ->getFont()
                        ->setSize($superHeadSize)
                        ->setBold( true )
                        ->getColor()
                        ->setARGB($superHeadColor);

                    $superHead
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB($superHeadBgColor);

                    $superHead
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $spreadsheet->getActiveSheet()->setCellValue($col_start,$onglet);

                    $index_col = $index_col + 1;
                }

                $string_index_col = strval($index_col);

                $col_value = Coordinate::stringFromColumnIndex($headColumn);
                $col_start = $col_value.$string_index_col;

                $col_value = Coordinate::stringFromColumnIndex(count($heads) + $headColumn - 1);
                $col_end = $col_value.$string_index_col;

                $headLine = "$col_start:$col_end";

                $headStyle = $spreadsheet
                    ->getActiveSheet()
                    ->getStyle($headLine);

                $headStyle
                    ->getFont()
                    ->setSize($headSize)
                    ->getColor()
                    ->setARGB($headColor);

                $headStyle
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($headBgColor);

                $headStyle
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setvertical(Alignment::VERTICAL_CENTER);

                $dataStartLine = $index_col;

                foreach ($heads as $colIndex => $col) {
                    $col_value = Coordinate::stringFromColumnIndex($colIndex + $headColumn);
                    $spreadsheet->getActiveSheet()->setCellValue($col_value.$dataStartLine,$col);
                    $spreadsheet->getActiveSheet()->getColumnDimension($col_value)->setAutoSize(true);
                    $innerColumn = $innerColumn + 1;
                }

                foreach ($lines as $lineIndex => $line) {
                    foreach ($line as $colIndex => $col) {
                        $col_value = Coordinate::stringFromColumnIndex($colIndex + $headColumn);
                        $position = $col_value.($lineIndex + $dataStartLine + 1);
                        $spreadsheet->getActiveSheet()->setCellValue($position,$col);
                        $spreadsheet->getActiveSheet()->getColumnDimension($col_value)->setAutoSize(true);

                        $colStyle = $spreadsheet
                            ->getActiveSheet()
                            ->getStyle($position);

                        $colStyle
                            ->getFont()
                            ->setSize($contentSize);

                        $colStyle
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        if ($colIndex == 6 && strpos($col, "supprimé") !== false) {
                            $colStyle->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
                            $colStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("e7515a");
                            $colStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        }
                    }
                }
            }
            $headColumn = $headColumn + $innerColumn + 1;
        }

         if ( $spreadsheet->getSheetByName('Worksheet') && !is_null($spreadsheet->getSheetByName('Worksheet')) ) {
            $sheetIndex = $spreadsheet->getIndex(
                $spreadsheet->getSheetByName('Worksheet')
            );
            $spreadsheet->removeSheetByIndex($sheetIndex);
        }
        if ( $spreadsheet->getSheetByName('Worksheet 1') && !is_null($spreadsheet->getSheetByName('Worksheet 1')) ) {
            $sheetIndex = $spreadsheet->getIndex(
                $spreadsheet->getSheetByName('Worksheet 1')
            );
            $spreadsheet->removeSheetByIndex($sheetIndex);
        }

        return $spreadsheet;

    }

    /**
     * Private methode to create the spreadSheet
     * @param [] $export, the result of $this->formatExport
     * @param Spreadsheet|null $spreadsheet
     * @param int $index
     * 
     * @return Spreadsheet
     */
    public function buildExcel($fileName, $spreadsheet){
        $writer = new Xlsx($spreadsheet);
        
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        
        $writer->save($temp_file);

        return $temp_file;
    }

     /**
     * Get value of key in array
     * 
     * @param [] $array
     * @param string $key
     * @param mixed $default
     * 
     */
    public static function get($array, $key, $default=null){
        if(is_array($array) && $key){
            if(strpos($key, '|') > 0){
                list($field, $prop) = explode('|', $key);
                
                if(isset($array[$field][$prop]) && $array[$field][$prop]){
                    return $array[$field][$prop];
                }
            }
            else if(isset($array[$key]) && $array[$key] !== $default){
                return $array[$key];
            }
        }
        return $default;
    }
}
