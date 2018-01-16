<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

/**
 * Representa una etiqueta de tipo <i><b>&lt;property&gt;</b></i> del archivo <i><b>nutch-site.xml</b></i>
 *  
 * @author Yoniel Jorge Thomas Sosa
 * @copyright (c) 2014, Universidad de las Ciencias Informáticas - UCI
 */
class Property {

    private $name;
    private $value;
    private $description;
    private $ruta;

    /**
     * Crea una instancia de la clase <b>Property</b>
     * 
     * @param String $name Valor de la etiqueta <i><b>&lt;name&gt;</b></i>
     * @param String $value Valor de la etiqueta <i><b>&lt;value&gt;</b></i>
     * @param String $description Valor de la etiqueta <i><b>&lt;description&gt;</b></i>
     * @param String $ruta Ruta de la etiqueta <i><b>&lt;property&gt;</b></i>
     */
    public function __construct($name, $value, $description = "", $ruta = "") {
        $this->name = $name;
        $this->value = $value;
        $this->description = $description;
        $this->ruta = $ruta;
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }

    public function getDescription() {
        return $this->description;
    }

    /**
     * Devuelve la ruta de la etiqueta <i><b>&lt;property&gt;</b></i>
     * 
     * @return String Ruta de la etiqueta <i><b>&lt;property&gt;</b></i>
     */
    public function getRuta() {
        return $this->ruta;
    }

    public function setRuta($ruta) {
        $this->ruta = $ruta;
    }

    /**
     * Devuelve la ruta de la etiqueta <i><b>&lt;name&gt;</b></i>
     * 
     * @return String Ruta de la etiqueta <i><b>&lt;name&gt;</b></i>
     */
    public function getRutaOfName() {
        return $this->ruta . '/name/text()';
    }

    /**
     * Devuelve la ruta de la etiqueta <i><b>&lt;value&gt;</b></i>
     * 
     * @return String Ruta de la etiqueta <i><b>&lt;value&gt;</b></i>
     */
    public function getRutaOfValue() {
        return $this->ruta . '/value/text()';
    }

    /**
     * Devuelve la ruta de la etiqueta <i><b>&lt;description&gt;</b></i>
     * 
     * @return String Ruta de la etiqueta <i><b>&lt;description&gt;</b></i>
     */
    public function getRutaOfDescription() {
        return $this->ruta . '/description/text()';
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

}

?>
