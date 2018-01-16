<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

use ConfigOrion\ConfigOrionBundle\ExternalClases\GenerarXML;
use ConfigOrion\ConfigOrionBundle\Document\favorito;
use DOMDocument;
use ZipArchive;
use Exception;

/**
 * La clase <i><b>FavoritoXML</b></i> se encarga de exportar e importar 
 * los archivos favoritos de configuración registrados en el sistema. 
 *
 * @author Yoniel Jorge Thomas Sosa
 */
class FavoritoXML {

    private $nombreArchivoZIP;
    private $favoritoConfig;
    private $favoritoTempDir;
    private $favoritoTempExtractDir;
    private $parentDir;

    /**
     * Ubicación del archivo XSD utilizado para validar la estructura del archivo de configuración de favoritos
     */
    private $favoritoValidator = '../src/ConfigOrion/ConfigOrionBundle/ExternalClases/config.xsd';

    /**
     * Crea una instancia de la clase <b>FavoritoXML</b>
     * 
     * @param String $nombreArchivoZIP Nombre del archivo ZIP que será exportado o importado
     * @param String $favoritoConfig Nombre del archivo de configuración de favoritos
     * @param String $favoritoTempDir Ubicación del directorio temporal utilizado para importar los archivos favoritos
     * @param String $favoritoTempExtractDir Ubicación del directorio temporal utilizado para descomprimir los archivos favoritos
     * @param String $parentDir Nombre del directorio inicial que contiene los archivos favoritos de configuración
     */
    public function __construct($nombreArchivoZIP, $favoritoConfig, $favoritoTempDir, $favoritoTempExtractDir, $parentDir) {
        $this->nombreArchivoZIP = $nombreArchivoZIP;
        $this->favoritoConfig = $favoritoConfig;
        $this->favoritoTempDir = $favoritoTempDir;
        $this->favoritoTempExtractDir = $favoritoTempExtractDir;
        $this->parentDir = $parentDir;
    }

    /**
     * Crea el archivo principal de configuración de los archivos favoritos que serán exportados
     * 
     * @param array $favoritos Lista de objetos de tipo Document <b>"favorito"</b>
     * @return String Devuelve el contenido de archivo XML que contiene la 
     * configuración de los archivos favoritos que serán exportados
     */
    private function generarConfiguracionXML($favoritos) {
        $xml = new GenerarXML();
        // Creando la etiqueta <favorito>
        $favoritoTag = $xml->crearTag('favorito');
        foreach ($favoritos as $favorito) {
            // Creando la etiqueta <archivo> con los atributos: "nombre", "ruta", "instancia" y "formato" 
            $archivoTag = $xml->crearTagConValores('archivo', array("nombre", "ruta", "formato", "instancia"), array($favorito->getNombreArchivo(),
                $favorito->getRutaArchivo(), $favorito->getFormato(), $favorito->getNombreInstancia()));
            // Creando un nodo tipo texto para la descripción del favorito
            $xml->crearTexto($favorito->getDescripcion(), $archivoTag);
            // Creando la estructura de etiquetas            
            $favoritoTag->appendChild($archivoTag);
        }
        $xml->asignarRaiz($favoritoTag);
        // Guardando la estructura del XML en un fichero        
        return $xml->getXML();
    }

    /**
     * Crea un archivo ZIP a partir de un conjunto de archivos favoritos de 
     * configuración que luego serán exportados.
     * 
     * @param array $favoritos Lista de objetos de tipo Document <b>"favorito"</b>      
     * @return boolean Devuelve <b>TRUE</b> si se creó correctamente el archivo ZIP, <b>FALSE</b> en caso contrario.  
     */
    public function exportar($favoritos) {
        // Creando el archivo de configuración de favoritos
        $configuracionFavorito = $this->generarConfiguracionXML($favoritos);
        // Creando el archivo ZIP
        $archivoZIP = new ZipArchive();
        if ($archivoZIP->open($this->getNombreArchivoZIP(), ZipArchive::OVERWRITE) === TRUE) {
            // Creando el directorio padre            
            $archivoZIP->addEmptyDir($this->parentDir);
            // Creando un directorio con el nombre de cada instancia
            foreach ($favoritos as $archivoFavorito) {
                $instanciaFavorito = $this->replaceCharacter($archivoFavorito->getNombreInstancia());
                $nombreFavorito = $this->replaceCharacter($archivoFavorito->getNombreArchivo());
                $contenidoFavorito = $archivoFavorito->getContenido();
                $formatoFavorito = strtolower($archivoFavorito->getFormato());                
                // Estableciendo la extension del archivo
                if ($formatoFavorito !== 'null') {
                    $extension = '.' . $formatoFavorito;
                } else {
                    $extension = '';
                }                    
                // Construyendo la ruta del nuevo subdirectorio
                $subDirectorio = $this->parentDir . '/' . $instanciaFavorito;
                $archivoZIP->addEmptyDir($subDirectorio);
                // Incluyendo el contenido del archivo favorito en un fichero dentro de la instancia
                $archivoZIP->addFromString($subDirectorio . '/' . $nombreFavorito . $extension, $contenidoFavorito);
            }
            // Incluyendo fichero de configuracion de exportar favorito            
            $archivoZIP->addFromString($this->parentDir . '/' . $this->favoritoConfig, $configuracionFavorito);
            // Cerrando el archivo ZIP
            $archivoZIP->close();
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Normaliza el nombre de un directorio para evitar una codificación no válida
     * cuando se cree el archivo ZIP
     * 
     * @param String $input Nombre del directorio
     * @return String Devuelve el nombre del directorio normalizado
     */
    private function replaceCharacter($input) {
        $character = array('á', 'é', 'í', 'ó', 'ú', 'ñ');
        $replace = array('a', 'e', 'i', 'o', 'u', 'n');
        return str_replace($character, $replace, $input);
    }

    /**
     * Extrae un archivo ZIP en el directorio temporal utilizado para descomprimir los archivos favoritos
     * 
     * @param String $fileName Nombre del archivo ZIP
     * @return boolean Devuelve <b>TRUE</b> si el archivo ZIP fue extraído correctamente, <b>FALSE</b> en caso contrario.
     */
    private function extractZipFile($fileName) {        
        $filePath = $this->favoritoTempDir . '/' . $fileName;
        if (!is_dir($this->favoritoTempExtractDir)) {
            mkdir($this->favoritoTempExtractDir);
        }
        if (is_dir($this->favoritoTempExtractDir)) {
            $zipFile = new ZipArchive();
            if ($zipFile->open($filePath) === TRUE) {
                return $zipFile->extractTo($this->favoritoTempExtractDir);
            }
        }
        return FALSE;
    }

    /**
     * Importa un archivo favorito de configuración
     * 
     * @param String $fileName Nombre del archivo ZIP
     * @return array Devuelve una lista de objetos de tipo Document <b>"favorito"</b> que
     * contiene la información de los archivos favoritos de configuración que fueron importados.
     * Lanza una excepción de tipo <b>Exception</b> en caso de que ocurra un error mientras se importa la configuración.
     * @throws Exception 
     */
    public function importar($fileName) {
        // Creando la lista de objetos Document de tipo favorito
        $favoritosDocument = array();
        if ($this->extractZipFile($fileName)) {
            // Creando la ruta del archivo de configuracion de favoritos            
            $extractDir = $this->favoritoTempExtractDir . '/' . $this->parentDir;
            $configPath = $extractDir . '/' . $this->favoritoConfig;
            // Comprobando que existe el archivo "config.xml"
            if (is_file($configPath)) {
                // Cargando el fichero de configuracion de favoritos
                $xml = new DOMDocument();
                $xml->load($configPath);
                if (@$xml->schemaValidate($this->favoritoValidator)) {
                    // Obteniendo todas las etiquetas de tipo archivo
                    $archivosTag = $xml->documentElement->getElementsByTagName('archivo');
                    for ($i = 0; $i < $archivosTag->length; $i++) {
                        // Obteniendo la informacion del archivo favorito
                        $archivo = $archivosTag->item($i);
                        $nombre = $archivo->getAttribute('nombre');
                        $ruta = $archivo->getAttribute('ruta');
                        $formato = $archivo->getAttribute('formato');
                        if ($formato !== 'NULL') {
                            $extension = '.' . strtolower($formato);
                        } else {
                            $extension = '';
                        }
                        $descripcion = $archivo->textContent;
                        $instanciaNombre = $archivo->getAttribute('instancia');
                        // Creando la ruta donde se ubican los archivos de cada instancia
                        $instanciaFavoritoPath = $extractDir . '/' . $this->replaceCharacter($instanciaNombre)
                                . '/' . $nombre . $extension;
                        // Obteniendo el contenido del archivo favorito de cada instancia
                        if (is_file($instanciaFavoritoPath)) {
                            $contenido = file_get_contents($instanciaFavoritoPath);

                            $finfo = finfo_open(FILEINFO_MIME_TYPE); // Inicializando el recurso                             
                            $tipoMime = finfo_file($finfo, $instanciaFavoritoPath);  // Obtiene el tipo MIME para un fichero específico          
                            finfo_close($finfo);   // Destruyendo el recurso  

                            if ($tipoMime === 'application/xml') {
                                $contenidoXML = new DOMDocument();
                                if (!@$contenidoXML->loadXML($contenido)) {
                                    throw new Exception('El archivo favorito que intenta importar presenta varias inconsistencias.', '200');
                                }
                            }

                            // Creando el Document de tipo favorito
                            $favorito = new favorito();
                            $favorito->setNombreArchivo($nombre);
                            $favorito->setRutaArchivo($ruta);
                            $favorito->setFormato($formato);
                            $favorito->setDescripcion($descripcion);
                            $favorito->setNombreInstancia($instanciaNombre);
                            $favorito->setContenido($contenido);
                            // Adicionando el favorito a la lista
                            $favoritosDocument[] = $favorito;
                        } else {
                            throw new Exception('El archivo favorito que intenta importar presenta varias inconsistencias.', '200');
                        }
                    }
                    $this->eliminarDirectorio($this->favoritoTempDir);
                } else {
                    throw new Exception('El archivo favorito que intenta importar no es válido.', '200');
                }
            } else {
                throw new Exception('El archivo favorito que intenta importar no es válido.', '200');
            }
        }
        return $favoritosDocument;
    }

    /**
     * Elimina un directorio completo
     * 
     * @param String $directorio Ruta del directorio
     */
    private function eliminarDirectorio($directorio) {
        foreach (glob($directorio . "/*") as $archivo) {
            if (is_dir($archivo)) {
                $this->eliminarDirectorio($archivo);
            } else {
                unlink($archivo);
            }
        }
        rmdir($directorio);
    }

    public function getNombreArchivoZIP() {
        return $this->nombreArchivoZIP;
    }

    public function setNombreArchivoZIP($nombreArchivoZIP) {
        $this->nombreArchivoZIP = $nombreArchivoZIP;
    }

    public function getFavoritoConfiguracion() {
        return $this->favoritoConfig;
    }

    public function setFavoritoConfiguracion($favoritoConfig) {
        $this->favoritoConfig = $favoritoConfig;
    }

    public function getFavoritoTempDir() {
        return $this->favoritoTempDir;
    }

    public function setFavoritoTempDir($favoritoTempDir) {
        $this->favoritoTempDir = $favoritoTempDir;
    }

    public function getFavoritoTempExtractDir() {
        return $this->favoritoTempExtractDir;
    }

    public function setFavoritoTempExtractDir($favoritoTempExtractDir) {
        $this->favoritoTempExtractDir = $favoritoTempExtractDir;
    }

    public function getParentDir() {
        return $this->parentDir;
    }

    public function setParentDir($parentDir) {
        $this->parentDir = $parentDir;
    }

}

?>
