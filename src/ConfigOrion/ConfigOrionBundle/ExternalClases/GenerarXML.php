<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

use DOMDocument;

/**
 * La clase <i><b>GenerarXML</b></i> permite crear de forma dinámica un fichero XML
 */
class GenerarXML {

    /**
     *
     * @var String Instancia de la clase DOMDocument 
     */
    private $xml;

    /**
     * Crea una instancia de la clase <b>GenerarXML</b>
     */   
    function __construct() {
        $this->xml = new DOMDocument('1.0', 'UTF-8');
        $this->xml->preserveWhiteSpace = false;
        $this->xml->formatOutput = true;
    }

    /**
     * Crea una nueva etiqueta y la inserta dentro de una etiqueta padre en caso de que se especifique esta última.
     * 
     * @param String $nombreTag Nombre de la nueva etiqueta
     * @param array $attrs Lista de atributos de la nueva etiqueta
     * @param array $valores Lista de valores correspondientes a cada atributo
     * @param DOMElement $padreTag Etiqueta padre
     * @return DOMElement|boolean Devuelve un objeto de tipo <b>DOMElement</b> como la nueva etiqueta o <b>FALSE</b> en caso de error.
     */
    function crearTagConValores($nombreTag, $attrs, $valores, $padreTag = null) {
        //  Valido que la cantidad de propiedades sea igual a la cantidad de valores
        if (count($attrs) == count($valores)) {
            $tag = $this->xml->createElement($nombreTag);       //  Creo el TAG
            //  Defino los atributos y sus valores
            for ($i = 0; $i < count($attrs); $i++) {
                $tag->setAttribute($attrs[$i], $valores[$i]);
            }
            //  Valido si tiene padre
            if ($padreTag == NULL) {
                return $tag;
            } else {
                $padreTag->appendChild($tag);   //  Asigno el hijo al padre
                return $tag;
            }
        } else {
            return false;
        }
    }

    /**
     * Crea una nueva etiqueta sin atributos y la inserta dentro de una etiqueta padre en caso de que se especifique esta última.
     * 
     * @param String $nombreTag Nombre de la nueva etiqueta
     * @param DOMElement $padreTag Etiqueta padre
     * @return DOMElement Devuelve un objeto de tipo <b>DOMElement</b> como la nueva etiqueta
     */
    function crearTag($nombreTag, $padreTag = null) {
        $tag = $this->xml->createElement($nombreTag);       //  Creo el TAG
        //  Valido si tiene padre
        if ($padreTag == NULL) {
            return $tag;
        } else {
            $padreTag->appendChild($tag);   //  Asigno el hijo al padre
            return $tag;
        }
    }

    /**
     * Inserta una cadena de texto dentro de una etiqueta
     * 
     * @param String $texto Cadena de texto
     * @param DOMElement $padreTag Etiqueta
     * @return boolean Devuelve <b>TRUE</b> en caso de éxito o <b>FALSE</b> en caso de error.
     */
    function crearTexto($texto, $padreTag) {
        if ($padreTag != null) {
            $t = $this->xml->createTextNode($texto);
            $padreTag->appendChild($t);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Establece una etiqueta como la raíz del archivo XML
     * 
     * @param type $raiz Etiqueta raíz
     * @return boolean Devuelve <b>TRUE</b> en caso de éxito o <b>FALSE</b> en caso de error.
     */
    function asignarRaiz($raiz) {
        if ($this->xml->appendChild($raiz)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Guarda el contenido del XML en un archivo
     * 
     * @param String $nombreArchivo Nombre del archivo
     * @return boolean Devuelve <b>TRUE</b> en caso de éxito o <b>FALSE</b> en caso de error.
     */
    function guardarXML($nombreArchivo) {
        if ($this->xml->save($nombreArchivo . '.xml')) {
            return true;
        } else {
            return false;
        }
    }

    
    /**
     * Guarda la estructura de un XML cualquiera en un archivo
     * 
     * @param String $contenido Estructura del XML
     * @param type $ruta_archivo Ruta del archivo
     * @return boolean Devuelve <b>TRUE</b> en caso de éxito o <b>FALSE</b> en caso de error.
     */ 
    public function saveXMLToFile($contenido, $ruta_archivo) {
        if (is_file($ruta_archivo)) {
            $this->xml->loadXML($contenido);
            $xml_content = $this->xml->saveXML();
            $newContent = html_entity_decode($xml_content, null, "UTF-8");
            $file = fopen($ruta_archivo, "w");
            fwrite($file, $newContent);
            fclose($file);
            return TRUE;
        } else
            return FALSE;
    }

    /**
     * Devuelve el contenido del archivo XML
     * 
     * @return String Contenido del archivo XML
     */
    public function getXML() {
        return $this->xml->saveXML();
    }

}

?>
