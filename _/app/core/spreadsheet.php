<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
/**
 * Author: Rex
 * Infocenter Spreadsheet service
 */
class core_spreadsheet{
     private $path = '';
     private $active_sheet = null;
     public function __construct($file_path = null)
     {
          $this->path = $file_path;
          if($file_path){
               $this->active_sheet = IOFactory::load($file_path);
          }
     }
     /**
      * checkListToArray will read the spreadsheet content
      * Assuming spreadsheet content are Homeroom's Learning Log checklist
      * @return array
      */
     public function checkListToArray(){
          $result = [];
          foreach($this->active_sheet->getSheetNames() as $worksheet){
               if($active_ws = $this->active_sheet->getSheetByName($worksheet)){
                    $col = $active_ws->getHighestColumn();
                    $row = $active_ws->getHighestRow(); 
                    $highestColumnIndex = Coordinate::columnIndexFromString($col); // e.g. 5
     
                    $result[$worksheet] = $this->getAllContent($active_ws,$row,$highestColumnIndex);
               }
          }
          return $result;
     }

     /**
      * Get All content from active worksheet
      * @param  object $ws Worksheet object
      * @param int $highestRow last spreadsheet row from a worksheet
      * @param int $highestColumnIndex last spreadsheet column from a worksheet
      * @return array
      */
     private function getAllContent($ws,$highestRow,$highestColumnIndex){
          $content = [];
          $current_subject = '';
          for ($col = 1; $col <= $highestColumnIndex; ++$col) {
               for ($row = 1; $row <= $highestRow; ++$row) {
                    $value = $ws->getCellByColumnAndRow($col, $row)->getValue();
                    if(empty($value)){
                         break;
                    }
                    
                    if($row == 1){
                         $current_subject = $value;
                    }else{
                         $content[$current_subject][] = $value;
                    }
               }
          }
          return $content;
     }
}