<?php
namespace Pw\Exports;

use Dompdf\Dompdf;
use Dompdf\Options;

class ExportPdf {

    /**
     * Generate dompdf
     * @param string $html
     * @param array $opts
     * @return object
     */
    public function generateDomPdf($html, $opts = [])
    {

        /* Options */
        $isRemoteEnabled = $this->get($opts, "isRemoteEnabled", true);
        $paperType = $this->get($opts, "paperType", null);
        $paperOrientation = $this->get($opts, "paperOrientation", null);
        /* Options */

        $options = new Options();
        $options->set('isRemoteEnabled', $isRemoteEnabled);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        if ($paperType && $paperOrientation) {
            $dompdf->setPaper($paperType, $paperOrientation);
        }
        
        $dompdf->render();

        return $dompdf;
    }

    /**
     * Methode to format datas of export
     * @param [] $key_values, array collection ex: [["id": 1, "firstname": "John"], ["id": 2, "firstname": "Janne"]]
     * @param [] $key_labels, ex: ["Id" => "id", "Nom et Prénom" => "lastname_firstname"]
     * @param [] $dictonary
     * 
     * @return []
     */
    public function formatExport(
        $key_values, 
        $key_labels, 
        $dictonary = [] 
    ){

        $result = [] ;

        foreach ($key_labels as $key_label) {

            $key = $this->get($key_label, "key", null);
            $label = $this->get($key_label, "label", null);
            $model = $this->get($key_label, "model", null);
            $key_labels = $this->get($key_label, "key_labels", null);

            $format_data = [] ;
            $format_data["key"] = $key ;
            $format_data["label"] = $label ;
            $format_data["model"] = $model ;

            if (
                is_array($this->parseValue($key_values, $key)) && 
                $key_labels
            ) {
                $values = $this->parseValue($key_values, $key);
                $format_data["value"] = [] ;
                foreach($values as $value) {
                    $format_data["value"][] = $this->formatExport($value, $key_labels) ;
                }
            } else {
                $format_data["value"] = $this->format_value($key_values, $key_labels, $key, $dictonary) ;
            }
            

            $result[] = $format_data ;
        }


        return $result;
    }


    /**
     * Methode to format value of export
     * @param [] $key_values, array collection ex: [["id": 1, "firstname": "John"], ["id": 2, "firstname": "Janne"]]
     * @param [] $key_labels, ex: ["Id" => "id", "Nom et Prénom" => "lastname_firstname"]
     * @param string $key
     * @param [] $dictonary
     * 
     * @return [] | string
     */
    public function format_value($key_values, $key_labels, $key, $dictonary = []) {

        $result = null;
        if (
            $key_labels && 
            is_array($key_labels)
        ) {
            $result = [];
            $key_values = $key_values[$key];

            foreach ($key_labels as $key_label) {

                $_key = $this->get($key_label, "key", null);
                $_label = $this->get($key_label, "label", null);
                $_model = $this->get($key_label, "model", null);
                $_key_labels = $this->get($key_label, "key_labels", null);

                $format_data = [] ;
                $format_data["key"] = $_key ;
                $format_data["label"] = $_label ;
                $format_data["model"] = $_model ;

                $format_data["value"] = $this->format_value($key_values, $_key_labels, $_key, $dictonary) ;
                
                $result[] = $format_data ;
            }
        } else {
            $result = $this->parseValue($key_values, $key, $dictonary);
        }

        return $result;

    }

    /**
     * Private methode to format and render value of key
     * @param [] $data
     * @param string $key, ex : "id", "firstname", "firstname_lastname", "created_at"
     * @param [] $dictonary
     * 
     * @return string
     */
    private function parseValue($data, $key, $dictonary = [])
    {

        if (!array_key_exists($key, $data)) {
            return null;
        }

        $opt = null;
        $value = $data[$key];

        if ($value && $value === "undefined") {
            return null;
        }

        if (
            isset($dictonary[$key])
        ) {
            $opt = $dictonary[$key];
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
     * Get value of key in array
     * 
     * @param [] $array
     * @param string $key
     * @param mixed $default
     * 
     */
    private function get($array, $key, $default=null){
        if(is_array($array) && $key){
            if(strpos($key, '|') > 0){
                list($field, $prop) = explode('|', $key);
                
                if(isset($array[$field][$prop]) && $array[$field][$prop]){
                    return $array[$field][$prop];
                }
            }
            else if(isset($array[$key]) && $array[$key]){
                return $array[$key];
            }
        }
        return $default;
    }
}
