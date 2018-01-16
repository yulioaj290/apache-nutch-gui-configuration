<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

/**
 * La clase <b>InstanciaReport</b> se utiliza para generar los reportes de modificaciones.
 * Cada reporte que se genera contiene el nombre de la instancia de Nutch, el nombre y ubicación del archivo de
 * configuración. Además, por cada archivo se registra el nombre de la propiedad que fue modificada, 
 * el valor anterior, el valor actual, la fecha de la modificación y el total de modificaciones.
 */
class InstanciaReport {

    /**     
     * @var String Nombre de la instancia de Nutch 
     */
    private $instancia;
    
    /**     
     * @var String Nombre del archivo de configuración 
     */
    private $archivo;
    
    /**     
     * @var String Ubicación del archivo de configuración  
     */
    private $ubicacion;
    
    /**     
     * @var array Listado de modificaciones que contiene en cada posición
     * un arreglo "X" estructuado de la siguiente forma: 
     * X[1]=> nombre de la propiedad
     * X[2]=> valor anterior
     * X[3]=> valor actual
     * X[4]=> fecha de la modificación 
     */
    private $modificaciones;
    
    /**     
     * @var integer Total de modificaciones
     */
    private $count;
   
    private $wrap;

    /**
     * Crea un instancia de la clase <b>InstanciaReport</b>
     * 
     * @param String $instancia Nombre de la instancia de Nutch
     * @param boolean $wrap Divide un texto en varias líneas si se establece el valor <b>TRUE</b>.
     * Valor por defecto <b>FALSE</b>
     */
    public function __construct($instancia, $wrap = FALSE) {
        $this->instancia = $instancia;
        $this->modificaciones = array();
        $this->count = 0;
        $this->wrap = $wrap;
    }

    public function getInstancia() {
        return $this->instancia;
    }

    public function setInstancia($instancia) {
        $this->instancia = $instancia;
    }

    public function getArchivo() {
        return $this->archivo;
    }

    public function setArchivo($archivo) {
        $this->archivo = $archivo;
    }

    public function getUbicacion() {
        return $this->ubicacion;
    }

    public function setUbicacion($ubicacion) {
        $this->ubicacion = $ubicacion;
    }

    public function getModificaciones() {
        return $this->modificaciones;
    }    
    
    /**
     * Inserta un nuevo registro de modificación
     * 
     * @param array $modificacion Datos de la modificación
     */
    public function addModificacion($modificacion) {
        $modificacion[1] = $this->wrapText($modificacion[1]);
        $modificacion[2] = $this->wrapText($modificacion[2]);
        $this->modificaciones[] = $modificacion;
        $this->count++;
    }
    
    public function getCount() {
        return $this->count;
    }
    
    /**
     * Divide un texto en varias líneas si este sobre pasa los 15 caracteres
     * 
     * @param String $text Contenido del texto
     * @return String Contenido del texto dividido en varias líneas
     */
    private function wrapText($text) {
        return wordwrap($text, 15, "\n", TRUE);
    }

}

?>
