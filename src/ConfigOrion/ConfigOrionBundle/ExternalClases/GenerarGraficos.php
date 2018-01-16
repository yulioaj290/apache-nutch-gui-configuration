<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

class GenerarGraficos {

    /**
     * Devuelve un array con dos arrays internos. El 1ro contiene los archivos 
     * mas modificados. El 2do los archivos menos modificados.
     * 
     * @param DocumentManager $dm Document Manager
     * @param integer $NoUltimos Filtro de ultimos registros
     * @param string $archivo Filtro de archivo
     * @param string $fecha Filtro de fecha
     * @return array Devuelve un array con dos arrays internos.
     */
    public static function modificacionesPorArchivo($dm, $NoUltimos = '', $archivo = '', $fecha = '') {
        $arrayModsDocs = array();
        $arrayKeys = array();
        $k = 0;
        // Verificando si se filtra por archivo
        if ($archivo != '') {
            // Filtrando por Instancia | Plugin | Archivo
            $dataArchivo = explode('|', $archivo);
            // Obteniendo todas las modificaciones si el filtro es una instancia
            if ($dataArchivo[0] == 'instancia') {
                $instanciaDoc = $dm->getRepository('ConfigOrionBundle:instancia')->findOneById($dataArchivo[1]);
                $pluginsDocs = $instanciaDoc->getPlugins();
                foreach ($pluginsDocs as $pluginDoc) {
                    // Obteniendo modificaciones
                    $modificacionesByArchivos = self::getModificacionesByArchivos($arrayKeys, $arrayModsDocs, $k, $instanciaDoc, $pluginDoc);
                    $arrayKeys = $modificacionesByArchivos[0];
                    $arrayModsDocs = $modificacionesByArchivos[1];
                    $k = $modificacionesByArchivos[2];
                }
                // Obteniendo modificaciones
                $modificacionesByArchivos = self::getModificacionesByArchivos($arrayKeys, $arrayModsDocs, $k, $instanciaDoc);
                $arrayKeys = $modificacionesByArchivos[0];
                $arrayModsDocs = $modificacionesByArchivos[1];
                $k = $modificacionesByArchivos[2];

                // Obteniendo todas las modificaciones si el filtro es un plugin
            } elseif ($dataArchivo[0] == 'plugin') {
                $pluginDoc = $dm->getRepository('ConfigOrionBundle:plugin')->findOneById($dataArchivo[1]);

                // Obteniendo modificaciones
                $modificacionesByArchivos = self::getModificacionesByArchivos($arrayKeys, $arrayModsDocs, $k, NULL, $pluginDoc);
                $arrayKeys = $modificacionesByArchivos[0];
                $arrayModsDocs = $modificacionesByArchivos[1];
                $k = $modificacionesByArchivos[2];

                // Obteniendo todas las modificaciones si el filtro es un archivo
            } elseif ($dataArchivo[0] == 'archivo') {
                $archivoDoc = $dm->getRepository('ConfigOrionBundle:archivo')->findOneById($dataArchivo[1]);
                $modificacionesDocs = $archivoDoc->getModificaciones();
                if (count($modificacionesDocs) != 0) {
                    foreach ($modificacionesDocs as $modificacionDoc) {
                        $arrayKeys [$k] = $modificacionDoc->getPropiedad() . '|' . $k;
                        $arrayModsDocs[$k] = $modificacionDoc;
                        $k++;
                    }
                } else {
                    $arrayKeys [$k] = $modificacionDoc->getPropiedad() . '|' . $k;
                    $arrayModsDocs[$k] = 0;
                    $k++;
                }
            }
        } else {
            // Si no se ha filtrado por archivo
            $instanciasDocs = $dm->getRepository('ConfigOrionBundle:instancia')->findAll();

            foreach ($instanciasDocs as $instanciaDoc) {
                $pluginsDocs = $instanciaDoc->getPlugins();
                foreach ($pluginsDocs as $pluginDoc) {

                    // Obteniendo modificaciones
                    $modificacionesByArchivos = self::getModificacionesByArchivos($arrayKeys, $arrayModsDocs, $k, $instanciaDoc, $pluginDoc);
                    $arrayKeys = $modificacionesByArchivos[0];
                    $arrayModsDocs = $modificacionesByArchivos[1];
                    $k = $modificacionesByArchivos[2];
                }

                // Obteniendo modificaciones
                $modificacionesByArchivos = self::getModificacionesByArchivos($arrayKeys, $arrayModsDocs, $k, $instanciaDoc);
                $arrayKeys = $modificacionesByArchivos[0];
                $arrayModsDocs = $modificacionesByArchivos[1];
                $k = $modificacionesByArchivos[2];
            }
        }

        // Verifico si al final se obtuvieron modificaciones
        if (count($arrayKeys) != 0) {
            $arrayModsArchivo = array_combine($arrayKeys, $arrayModsDocs);
        } else {
            $arrayModsArchivo = array('Sin archivos|0' => 0);
        }

        // Filtrando los resultados por fecha
        if ($fecha != '') {
            $fechas = explode(' - ', $fecha);
            $dateInicio = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[0] . ' 00:00:00');
            $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[1] . ' 24:00:00');
            $h = 0;
            foreach ($arrayModsArchivo as $key => $arrayModArch) {
                if ((!is_numeric($arrayModArch)) && ($dateInicio > $arrayModArch->getFecha() || $arrayModArch->getFecha() > $dateFin)) {
                    unset($arrayModsArchivo[$key]);
                }
                $h++;
            }
        }

        // Ordenando arreglo por fecha
        uasort($arrayModsArchivo, array(new GenerarGraficos, 'cmpModificacionesByDateDES'));

        // Filtrando por Ultimos registros
        if ($NoUltimos != '') {
            array_splice($arrayModsArchivo, $NoUltimos);
        }

        // Organizando el array
        $arrayMods = array();
        foreach ($arrayModsArchivo as $key => $arrayModArch) {
            $exist = false;
            $trueKey = explode('|', $key);
            if (count($arrayMods) == 0) {
                if (is_numeric($arrayModArch) && $arrayModArch == 0) {
                    $arrayMods[$trueKey[0]] = 0;
                } else {
                    $arrayMods[$trueKey[0]] = 1;
                }
            } else {
                foreach ($arrayMods as $archivoRuta => $mods) {
                    if ($archivoRuta == $trueKey[0]) {
                        $exist = true;
                        $arrayMods[$archivoRuta] += 1;
                    }
                }
                if (!$exist) {
                    if (is_numeric($arrayModArch) && $arrayModArch == 0) {
                        $arrayMods[$trueKey[0]] = 0;
                    } else {
                        $arrayMods[$trueKey[0]] = 1;
                    }
                }
                $exist = false;
            }
        }

        // Obteniendo propiedad del sistema con cantidad maxima de archivos a representar
        $cantModsProperty = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'cantidad_archivos_graficos'))->getValor();
        $arrayModsArchivoASC = array();
        $arrayModsArchivoDES = array();

        //Verificando si hay archivos con modificaciones, si no hay, se pone a cero
        if (count($arrayMods) == 0) {
            $arrayModsArchivoASC[0] = array('archivos', 0);
            $arrayModsArchivoDES[0] = array('archivos', 0);
        } else {
            asort($arrayMods);
            $i = 0;
            foreach ($arrayMods as $key => $value) {
                if ($i == $cantModsProperty) {
                    break;
                } else {
                    $arrayModsArchivoASC[$i] = array($key, $value);
                    $i++;
                }
            }
            arsort($arrayMods);
            $i = 0;
            foreach ($arrayMods as $key => $value) {
                if ($i == $cantModsProperty) {
                    break;
                } else {
                    $arrayModsArchivoDES[$i] = array($key, $value);
                    $i++;
                }
            }
        }

        return array($arrayModsArchivoASC, $arrayModsArchivoDES);
    }

    /**
     * Obtiene las modificaciones segun el filtro de Archivo
     * 
     * @param type $arrayKeys
     * @param type $arrayModsDocs
     * @param type $k
     * @param type $instanciaDoc
     * @param type $pluginDoc
     * @return type
     */
    private static function getModificacionesByArchivos($arrayKeys, $arrayModsDocs, $k, $instanciaDoc, $pluginDoc = NULL) {
        if ($pluginDoc != NULL) {
            $archivosDocs = $pluginDoc->getArchivos();
        } else {
            $archivosDocs = $instanciaDoc->getArchivos();
        }
        foreach ($archivosDocs as $archivoDoc) {
            $modificacionesDocs = $archivoDoc->getModificaciones();
            if (count($modificacionesDocs) != 0) {
                foreach ($modificacionesDocs as $modificacionDoc) {
                    if ($instanciaDoc != NULL) {
                        $arrayKeys [$k] = $instanciaDoc->getNombre();
                    }
                    if ($pluginDoc != NULL) {
                        $arrayKeys [$k] .= '/' . $pluginDoc->getNombre();
                    }
                    $arrayKeys [$k] .= '/' . $archivoDoc->getNombre();
                    if ($archivoDoc->getFormato() == 'NULL') {
                        $arrayKeys [$k] .= '|' . $k;
                    } else {
                        $arrayKeys [$k] .= '.' . strtolower($archivoDoc->getFormato()) . '|' . $k;
                    }
                    $arrayModsDocs[$k] = $modificacionDoc;
                    $k++;
                }
            } else {
                if ($instanciaDoc != NULL) {
                    $arrayKeys [$k] = $instanciaDoc->getNombre();
                }
                if ($pluginDoc != NULL) {
                    $arrayKeys [$k] .= '/' . $pluginDoc->getNombre();
                }
                $arrayKeys [$k] .= '/' . $archivoDoc->getNombre();
                if ($archivoDoc->getFormato() == 'NULL') {
                    $arrayKeys [$k] .= '|' . $k;
                } else {
                    $arrayKeys [$k] .= '.' . strtolower($archivoDoc->getFormato()) . '|' . $k;
                }
                $arrayModsDocs[$k] = 0;
                $k++;
            }
        }

        return array($arrayKeys, $arrayModsDocs, $k);
    }

    /**
     * Devuelve un array con la cantidad de modificaciones por tipo.
     * 
     * @param DocumentManager $dm Document Manager
     * @param integer $NoUltimos Filtro de ultimos registros
     * @param string $archivo Filtro de archivo
     * @param string $fecha Filtro de fecha
     * @return array Devuelve un array con la cantidad de modificaciones por tipo.
     */
    public static function modificacionesPorTipo($dm, $NoUltimos = '', $archivo = '', $fecha = '') {
        $modificacionesDocs = array();
        $k = 0;
        if ($archivo == '') {
            $modificacionesDocs[0] = $dm->getRepository('ConfigOrionBundle:modificacion')->findByFechaSortDES();
        } else {
            // Filtrando por Instancia | Plugin | Archivo
            $dataArchivo = explode('|', $archivo);
            if ($dataArchivo[0] == 'instancia') {
                $instanciaDoc = $dm->getRepository('ConfigOrionBundle:instancia')->findOneById($dataArchivo[1]);
                $pluginsDocs = $instanciaDoc->getPlugins();
                foreach ($pluginsDocs as $pluginDoc) {
                    $archivosDocs = $pluginDoc->getArchivos();
                    foreach ($archivosDocs as $archivoDoc) {
                        $modificacionesDocs[$k] = $archivoDoc->getModificaciones();
                        $k++;
                    }
                }
                $archivosIDocs = $instanciaDoc->getArchivos();
                foreach ($archivosIDocs as $archivoIDoc) {
                    $modificacionesDocs[$k] = $archivoIDoc->getModificaciones();
                    $k++;
                }
            } elseif ($dataArchivo[0] == 'plugin') {
                $pluginDoc = $dm->getRepository('ConfigOrionBundle:plugin')->findOneById($dataArchivo[1]);
                $archivosDocs = $pluginDoc->getArchivos();
                foreach ($archivosDocs as $archivoDoc) {
                    $modificacionesDocs[$k] = $archivoDoc->getModificaciones();
                    $k++;
                }
            } elseif ($dataArchivo[0] == 'archivo') {
                $archivoDoc = $dm->getRepository('ConfigOrionBundle:archivo')->findOneById($dataArchivo[1]);
                $modificacionesDocs[$k] = $archivoDoc->getModificaciones();
                $k++;
            }
        }

        $arrayModsTipo = array();
        $newModificacionesDocs = new \Doctrine\Common\Collections\ArrayCollection();
        for ($l = 0; $l < count($modificacionesDocs); $l++) {
            foreach ($modificacionesDocs[$l] as $modificacionDoc) {
                // Filtrando los resultados por fecha
                if ($fecha != '') {
                    $fechas = explode(' - ', $fecha);
                    $dateInicio = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[0] . ' 00:00:00');
                    $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[1] . ' 24:00:00');
                    if ($dateInicio <= $modificacionDoc->getFecha() && $modificacionDoc->getFecha() <= $dateFin) {
                        $newModificacionesDocs->add($modificacionDoc);
                    }
                } else {
                    $newModificacionesDocs->add($modificacionDoc);
                }
            }
        }

        // Ordenando las modificaciones por fecha si se ha elegido filtrar por archivo
        if ($archivo != '') {
            $pureArrayModificacionesDocs = $newModificacionesDocs->toArray();
            uasort($pureArrayModificacionesDocs, array(new GenerarGraficos, 'cmpModificacionesByDateDES'));
            $newModificacionesDocs = new \Doctrine\Common\Collections\ArrayCollection($pureArrayModificacionesDocs);
        }

        $j = 0;
        $arrayModsTipo = array('INSERTAR' => 0, 'MODIFICAR' => 0, 'ELIMINAR' => 0);
        foreach ($newModificacionesDocs as $modificacionDoc) {
            foreach ($arrayModsTipo as $tipo => $mod) {
                if ($tipo == $modificacionDoc->getTipoModificacion()) {
                    $arrayModsTipo[$tipo] += 1;
                }
            }

            // Filtrando los resultados por Ultimos registros
            // El arreglo de modificaciones debe llegar a este punto ordenado Descendentemente por Fecha
            $j++;
            if ($NoUltimos != '' && $j >= $NoUltimos) {
                break;
            }
        }

        $arrayDataModsTipo = array();
        $i = 0;
        foreach ($arrayModsTipo as $dataTipo => $cantDataMods) {
            $arrayDataModsTipo[$i] = array($dataTipo, $cantDataMods);
            $i++;
        }

        return $arrayDataModsTipo;
    }

    /**
     * Devuelve un array cuyas llaves son las fechas en las que se hizo las modificaciones,
     * y los valores constituyen la cantidad de modificaciones de cada fecha.
     * 
     * @param DocumentManager $dm Document Manager
     * @param integer $NoUltimos Filtro de ultimos registros
     * @param string $archivo Filtro de archivo
     * @param string $fecha Filtro de fecha
     * @return array
     */
    public static function cantidadModificaciones($dm, $NoUltimos = '', $archivo = '', $fecha = '') {

        $modificacionesDocs = array();
        $k = 0;
        if ($archivo == '') {
            $modificacionesDocs[0] = $dm->getRepository('ConfigOrionBundle:modificacion')->findByFechaSortDES();
        } else {
            // Filtrando por Instancia | Plugin | Archivo
            $dataArchivo = explode('|', $archivo);
            if ($dataArchivo[0] == 'instancia') {
                $instanciaDoc = $dm->getRepository('ConfigOrionBundle:instancia')->findOneById($dataArchivo[1]);
                $pluginsDocs = $instanciaDoc->getPlugins();
                foreach ($pluginsDocs as $pluginDoc) {
                    $archivosDocs = $pluginDoc->getArchivos();
                    foreach ($archivosDocs as $archivoDoc) {
                        $modificacionesDocs[$k] = $archivoDoc->getModificaciones();
                        $k++;
                    }
                }
                $archivosIDocs = $instanciaDoc->getArchivos();
                foreach ($archivosIDocs as $archivoIDoc) {
                    $modificacionesDocs[$k] = $archivoIDoc->getModificaciones();
                    $k++;
                }
            } elseif ($dataArchivo[0] == 'plugin') {
                $pluginDoc = $dm->getRepository('ConfigOrionBundle:plugin')->findOneById($dataArchivo[1]);
                $archivosDocs = $pluginDoc->getArchivos();
                foreach ($archivosDocs as $archivoDoc) {
                    $modificacionesDocs[$k] = $archivoDoc->getModificaciones();
                    $k++;
                }
            } elseif ($dataArchivo[0] == 'archivo') {
                $archivoDoc = $dm->getRepository('ConfigOrionBundle:archivo')->findOneById($dataArchivo[1]);
                $modificacionesDocs[$k] = $archivoDoc->getModificaciones();
                $k++;
            }
        }

        $arrayMods = array();
        $newModificacionesDocs = new \Doctrine\Common\Collections\ArrayCollection();
        $j = 0;
        for ($l = 0; $l < count($modificacionesDocs); $l++) {
            foreach ($modificacionesDocs[$l] as $modificacionDoc) {
                $newModificacionesDocs->add($modificacionDoc);
            }
        }

        // Ordenando las modificaciones por fecha si se ha elegido filtrar por archivo
        if ($archivo != '') {
            $pureArrayModificacionesDocs = $newModificacionesDocs->toArray();
            uasort($pureArrayModificacionesDocs, array(new GenerarGraficos, 'cmpModificacionesByDateDES'));
            $newModificacionesDocs = new \Doctrine\Common\Collections\ArrayCollection($pureArrayModificacionesDocs);
        }

        foreach ($newModificacionesDocs as $modificacionDoc) {
            $exist = false;
            if (count($arrayMods) == 0) {
                $arrayMods[(String) $modificacionDoc->getFecha()->format('Y-m-d')] = 1;
            } else {
                foreach ($arrayMods as $fechaMod => $mod) {
                    if ($fechaMod == (String) $modificacionDoc->getFecha()->format('Y-m-d')) {
                        $exist = true;
                        $arrayMods[$fechaMod] += 1;
                    }
                }
                if (!$exist) {
                    $arrayMods[(String) $modificacionDoc->getFecha()->format('Y-m-d')] = 1;
                }
                $exist = false;
            }

            // Filtrando los resultados por Ultimos registros
            // El arreglo de modificaciones debe llegar a este punto ordenado Descendentemente por Fecha
            $j++;
            if ($NoUltimos != '' && $j >= $NoUltimos) {
                break;
            }
        }


        $arrayDataMods = array(/* array('2008-12-06', 5), array('2008-11-08', 6), array('2008-05-13', 8), array('2008-02-16', 9) */);
        $i = 0;
        foreach ($arrayMods as $dataFecha => $cantDataMods) {
            // Filtrando los resultados por fecha
            if ($fecha != '') {
                $fechas = explode(' - ', $fecha);
                $dateInicio = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[0] . ' 00:00:00');
                $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[1] . ' 24:00:00');
                $dateActual = \DateTime::createFromFormat('Y-m-d H:i:s', $dataFecha . ' 12:00:00');
                if ($dateInicio <= $dateActual && $dateActual <= $dateFin) {
                    $arrayDataMods[$i] = array((String) $dataFecha, $cantDataMods);
                    $i++;
                }
            } else {
                $arrayDataMods[$i] = array((String) $dataFecha, $cantDataMods);
                $i++;
            }
        }

        // Valor por defecto si no se encontraron Modificaciones
        if (count($arrayDataMods) == 0 && $fecha != '') {
            $fechas = explode(' - ', $fecha);
            $fecha1 = \DateTime::createFromFormat('d/m/Y', $fechas[1]);
            $arrayDataMods[0] = array($fecha1->format('Y-m-d'), 0);
        } elseif (count($arrayDataMods) == 0 && $fecha == '') {
            $fecha1 = new \DateTime();
            $arrayDataMods[0] = array($fecha1->format('Y-m-d'), 0);
        }
        return $arrayDataMods;
    }

    /**
     * Devuelve un array cuyas llaves son las fechas en las que se hizo las modificaciones,
     * y los valores constituyen la cantidad de modificaciones de cada fecha.
     * 
     * Recoge todas las fechas, desde la primera modificacion, hasta la fecha actual.
     * 
     * @param string $fecha Filtro de fecha
     * @return array
     */
    public static function cantidadModificacionesHistoricas($dm, $fecha = '') {
        $modificacionesDocs = $dm->getRepository('ConfigOrionBundle:modificacion')->findByFechaSortASC();

        $arrayMods = array();
        foreach ($modificacionesDocs as $modificacionDoc) {
            $exist = false;
            if (count($arrayMods) == 0) {
                $arrayMods[(String) $modificacionDoc->getFecha()->format('Y-m-d')] = 1;
            } else {
                foreach ($arrayMods as $fechaMod => $mod) {
                    if ($fechaMod == (String) $modificacionDoc->getFecha()->format('Y-m-d')) {
                        $exist = true;
                        $arrayMods[$fechaMod] += 1;
                    }
                }
                if (!$exist) {
                    $arrayMods[(String) $modificacionDoc->getFecha()->format('Y-m-d')] = 1;
                }
                $exist = false;
            }
        }
        // El array $arrayMods sale de este bucle ordenado por la clave fecha


        $arrayDataMods = array(/* array('2008-12-06', 5), array('2008-11-08', 6), array('2008-05-13', 8), array('2008-02-16', 9) */);
        $i = 0;
        foreach ($arrayMods as $dataFecha => $cantDataMods) {
            //$dateInicio = \DateTime::createFromFormat('d/m/Y', $fechas[0]);
            // Filtrando los resultados por fecha
            if ($fecha != '') {
                $fechas = explode(' - ', $fecha);
                $dateInicio = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[0] . ' 00:00:00');
                $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s', $fechas[1] . ' 24:00:00');
                $dateActual = \DateTime::createFromFormat('Y-m-d H:i:s', $dataFecha . ' 12:00:00');
                if ($dateInicio <= $dateActual && $dateActual <= $dateFin) {
                    $arrayDataMods[$i] = array((String) $dataFecha, $cantDataMods);
                    $i++;
                }
            } else {
                $arrayDataMods[$i] = array((String) $dataFecha, $cantDataMods);
                $i++;
            }
        }

        // Llenando fechas que no tengan modificaciones
        if (count($arrayDataMods) != 0) {
            $fechaInicial = \DateTime::createFromFormat('Y-m-d', $arrayDataMods[0][0]);
            if ($fecha != '') {
                $fechaActual = $dateFin;
            } else {
                $fechaActual = new \DateTime();
            }
            $exist = false;
            while ($fechaInicial != $fechaActual) {
                for ($n = 0; $n < count($arrayDataMods); $n++) {
                    if (\DateTime::createFromFormat('Y-m-d', $arrayDataMods[$n][0]) == $fechaInicial) {
                        $exist = true;
                    }
                }
                if (!$exist) {
                    $arrayDataMods[count($arrayDataMods)] = array((String) \date_format($fechaInicial, 'Y-m-d'), 0);
                } else {
                    $exist = false;
                }
                date_add($fechaInicial, date_interval_create_from_date_string('1 days'));
            }
        } elseif (count($arrayDataMods) == 0) {// Valor por defecto si no se encontraron Modificaciones
            $fecha = new \DateTime();
            $arrayDataMods[0] = array($fecha->format('Y-m-d'), 0);
        }
        return $arrayDataMods;
    }

    /**
     * Ordena un Array de Document Modificacion comparando las fechas que contiene en las claves de indices.
     *
     * @param Document $modA Objeto de tipo Document Modificacion   
     * @param Document $modB Objeto de tipo Document Modificacion
     * @return int Retorna 1 si la fecha de la primera modificación es mayor que la segunda, de lo contrario retorna -1
     */
    static function cmpModificacionesByDateDES($modA, $modB) {
        if ((is_numeric($modA) && $modA == 0) && (is_numeric($modB) && $modB == 0)) {
            return -1;
        } elseif ((is_numeric($modA) && $modA == 0) && !is_numeric($modB)) {
            return 1;
        } elseif (!is_numeric($modA) && (is_numeric($modB) && $modB == 0)) {
            return -1;
        } else {
            $a = $modA->getFecha();
            $b = $modB->getFecha();
            if ($a == $b) {
                return 0;
            }
        }
        return ($a < $b) ? 1 : -1;
    }

}

?>
