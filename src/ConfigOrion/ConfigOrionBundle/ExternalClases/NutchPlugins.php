<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

/**
 * Permite gestionar los plugins de Nutch
 *  
 * @author Yoniel Jorge Thomas Sosa
 * @copyright (c) 2014, Universidad de las Ciencias Informáticas - UCI
 */
class NutchPlugins {

    /**
     * @var array 
     */
    private $plugins;

    /**
     * @param array $plugins [opcional] Lista de plugins
     */
    public function __construct() {
        $this->plugins = array();
    }

    /**
     * Extrae la lista de plugins a partir del archivo de configuración <i>nutch-site.xml</i>
     * 
     * @param string $configuracion Valor de la propiedad <i>plugin.includes</i>
     *  del archivo <i>nutch-site.xml</i> &nbsp; Ej.: plugin1|plugin2-(ext)
     */
    public function extraerNutchPlugins($configuracion) {
        $result = &$this->plugins;
        $splitPlugins = function($found) use (&$result) {
                    $nombre = $found[1];
                    if (isset($found[4])) {
                        if (isset($found[5])) {
                            $plugin_ext = explode('|', $found[4]);
                            foreach ($plugin_ext as $plugin) {
                                $result[] = $nombre . '-' . $plugin;
                            }
                        } else {
                            $result[] = $nombre . '-' . $found[4];
                        }
                    } else {
                        $result[] = $nombre;
                    }
                };
        preg_replace_callback('|(\w+(\-\w+)*)(\-\((\w+(\|\w+)*)\))?|', $splitPlugins, $configuracion);
        unset($result);
    }

    /**
     * Comprueba si existe un plugin en la lista de plugins.
     *       
     * @param string $nombre Nombre completo del plugin
     * @return boolean Devuelve <b>TRUE</b> si existe el plugin, <b>FALSE</b> en caso contrario
     */
    public function isPlugin($nombre) {
        $found = FALSE;
        $size = count($this->plugins);
        for ($i = 0; $i < $size && !$found; $i++) {
            $plugin = $this->plugins[$i];
            if ($plugin == $nombre) {
                $found = TRUE;
            }
        }
        return $found;
    }

    /**
     * Adiciona un plugin a la lista de plugins
     * 
     * @param string $plugin Nombre completo del plugin
     * @return boolean Devuelve <b>TRUE</b> si se adicionó correctamente,
     *  <b>FALSE</b> en caso de que ya exista en la lista de plugins.
     */
    public function adicionarPlugin($plugin) {
        if (!$this->isPlugin($plugin)) {
            $this->plugins[] = $plugin;
            return TRUE;
        }
        else
            return FALSE;
    }

    
    /**
     * Elimina un plugin dado el nombre
     * 
     * @param type $nombre Nombre del plugin
     * @return boolean Si el plugin fue eliminado, devuelve <b>TRUE</b>, en caso contrario
     * devuelve <b>FALSE</b>
     */
    public function eliminarPlugin($nombre) {
        $eliminado = FALSE;
        $size = count($this->plugins);
        for ($i = 0; $i < $size && !$eliminado; $i++) {
            $plugin = $this->plugins[$i];
            if ($plugin == $nombre) {
                $eliminado = TRUE;
                unset($this->plugins[$i]);
            }
        }
        return $eliminado;
    }

    /**
     * Devuelve la lista de plugins
     * 
     * @return array
     */
    public function getPlugins() {
        return $this->plugins;
    }

    /**
     * Convierte la lista de plugins a una cadena en forma de expresión regular
     * 
     * @return string Cadena de plugins de la forma: plugin1|plugin2-(ext)
     */
    public function pluginsToRegex() {
        $output = array();
        $result = "";
        // Pre-procesando el nombre de los plugins para luego convertirlo a una expresión regular.         
        foreach ($this->plugins as $plugin) {
            $ext = strrchr($plugin, '-');
            if ($ext) {
                $ext = substr($ext, 1);
                $key = strstr($plugin, $ext, TRUE);
                if (isset($output[$key])) {
                    if (empty($output[$key]))
                        $output[$key] = $ext;
                    else
                        $output[$key] .= "|$ext";
                }
                else
                    $output[$key] = $ext;
            }
            if ($ext === FALSE) {
                $output[$plugin] = NULL;
            }
        }
        // Convirtiendo el nombre de los plugins a una expresión regular
        $size = count($output);
        $end = "|";
        foreach ($output as $plugin => $ext) {
            if ($size == 1)
                $end = "";
            if (!empty($ext))
                $result .= "$plugin($ext)$end";
            else
                $result .= "$plugin" . "$end";
            $size--;
        }
        return $result;  //Expresión regular
    }

}

?>
