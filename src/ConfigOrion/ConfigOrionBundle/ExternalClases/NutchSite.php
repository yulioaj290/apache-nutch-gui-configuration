<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

use ConfigOrion\ConfigOrionBundle\ExternalClases\Property;
use DOMDocument;
use DOMXPath;

/**
 * La clase <i><b>NutchSite</b></i> se encarga de la gestión de la configuración del archivo <i><b>nutch-site.xml</b></i>
 * 
 * @author Yoniel Jorge Thomas Sosa
 * @copyright (c) 2014, Universidad de las Ciencias Informáticas - UCI
 */
class NutchSite {

    /**
     *
     * @var String Contenido del archivo <i><b>nutch-site.xml</b></i>
     */
    private $configuracion;

    /**
     * Crea una instancia de la clase <b>NutchSite</b>
     * 
     * @param String $configuracion Contenido del archivo <i><b>nutch-site.xml</b></i>
     */
    public function __construct($configuracion) {
        $this->configuracion = new DOMDocument('1.0', 'UTF-8');
        $this->configuracion->preserveWhiteSpace = false;
        $this->configuracion->formatOutput = true;
        $this->configuracion->loadXML($configuracion);
    }

    /**
     * Elimina una etiqueta <i><b>&lt;property&gt;</b></i> a partir de la ruta
     * 
     * @param String $ruta Ruta de la etiqueta <i><b>&lt;property&gt;</b></i>
     */
    public function eliminarProperty($ruta) {
        $domXPath = new DOMXPath($this->configuracion);
        $property = $domXPath->query($ruta);
        if ($property !== FALSE) {
            if ($property->length == 1) {
                $this->configuracion->documentElement->removeChild($property->item(0));
            }
        }
    }

    /**
     * Actualiza el contenido de una etiqueta <i><b>&lt;property&gt;</b></i>
     * 
     * @param Property $property Instancia de la clase <b>Property</b>
     */
    public function actualizarProperty(Property $property) {
        $domXPath = new DOMXPath($this->configuracion);
        $oldProperty = $domXPath->query($property->getRuta());
        if ($oldProperty !== FALSE) {
            if ($oldProperty->length == 1) {
                $newPropertyDOM = $this->crearDOMProperty($property);
                $oldPropertyDOM = $oldProperty->item(0);
                $this->configuracion->documentElement->replaceChild($newPropertyDOM, $oldPropertyDOM);
            }
        }
    }

    /**
     * Crea un objeto de tipo <i><b>DOMElement</b></i> a partir de un objeto de tipo <i><b>Property</b></i>
     * 
     * @param Property $property Instancia de la clase <b>Property</b>
     * @return DOMElement Devuelve un objeto de tipo <i><b>DOMElement</b></i>
     */
    private function crearDOMProperty(Property $property) {
        // Creando las etiquetas <property>, <name>, <value> y <description>
        $nodeProperty = $this->configuracion->createElement("property");
        $nodeName = $this->configuracion->createElement("name");
        $nodeValue = $this->configuracion->createElement("value");
        $nodeDescription = $this->configuracion->createElement("description");
        // Creando los nodos tipo texto para los valores de las etiquetas
        $textName = $this->configuracion->createTextNode($property->getName());
        $textValue = $this->configuracion->createTextNode($property->getValue());
        $textDescription = $this->configuracion->createTextNode($property->getDescription());
        // Adicionando los nodos tipo texto a las etiquetas
        $nodeName->appendChild($textName);
        $nodeValue->appendChild($textValue);
        $nodeDescription->appendChild($textDescription);
        // Adicionando las etiquetas a <property>                
        $nodeProperty->appendChild($nodeName);
        $nodeProperty->appendChild($nodeValue);
        $nodeProperty->appendChild($nodeDescription);
        return $nodeProperty;
    }

    /**
     * Inserta una nueva etiqueta <i><b>&lt;property&gt;</b></i>
     * 
     * @param Property $property Instancia de la clase <b>Property</b>
     * @return Property Devuelve la etiqueta <i><b>&lt;property&gt;</b></i> insertada
     */
    public function adicionarProperty(Property $property) {
        // Creando la nueva etiqueta <property>
        $newProperty = $this->crearDOMProperty($property);
        // Adicionando la nueva etiqueta <property>
        $newNode = $this->configuracion->documentElement->appendChild($newProperty);
        $property->setRuta($newNode->getNodePath());
        return $property;
    }

    /**
     * Devuelve el contenido del archivo <i><b>nutch-site.xml</b></i>
     * 
     * @return String Contenido del XML
     */
    public function getContenidoXML() {
        return html_entity_decode($this->configuracion->saveXML(), NULL, 'UTF-8');
    }

    /**
     * Guarda el contenido de <i><b>nutch-site.xml</b></i> en un archivo
     * 
     * @param String $archivo Ruta del archivo
     */
    public function guardarContenidoXML($archivo) {
        $file = fopen($archivo, 'w');
        fwrite($file, $this->getContenidoXML());
        fclose($file);
    }

    /**
     * Obtiene el contenido de una etiqueta <i><b>&lt;property&gt;</b></i> a partir de la ruta
     * 
     * @param String $ruta Ruta de la etiqueta <i><b>&lt;property&gt;</b></i>
     * @return Property|boolean Devuelve un objeto de tipo <b>Property</b> o <b>FALSE</b>
     * en caso de error
     */
    public function getPropertyPorRuta($ruta) {
        $domXPath = new DOMXPath($this->configuracion);
        $property = $domXPath->query($ruta);
        if ($property !== FALSE) {
            if ($property->length == 1) {
                $property = simplexml_import_dom($property->item(0));
                return new Property($property->name, $property->value, $property->description, $ruta);
            }
        }
        else
            return FALSE;
    }

    /**
     * Obtiene el contenido de una etiqueta <i><b>&lt;property&gt;</b></i> a partir del valor de la
     * etiqueta <i><b>&lt;name&gt;</b></i>
     * 
     * @param String Valor de la etiqueta <i><b>&lt;name&gt;</b></i>
     * @return Property|boolean Devuelve un objeto de tipo <b>Property</b> o <b>FALSE</b>
     * en caso de error
     */
    public function getPropertyPorNombre($nombre) {
        $propiedades = $this->configuracion->getElementsByTagName('property');
        for ($i = 0; $i < $propiedades->length; $i++) {
            $property = $propiedades->item($i);
            $etiqueta = simplexml_import_dom($property);
            if ($etiqueta->name == $nombre) {
                $ruta = $property->getNodePath();
                return new Property($etiqueta->name, $etiqueta->value, $etiqueta->description, $ruta);
            }
        }
        return FALSE;
    }

}

?>
