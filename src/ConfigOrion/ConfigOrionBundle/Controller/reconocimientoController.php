<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use DOMDocument;
use ConfigOrion\ConfigOrionBundle\Document\sistema;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class reconocimientoController extends Controller {

    /**
     * Comprueba que el contenido de cada archivo registrado en el sistema
     * y el del correspondiente archivo real sean iguales.
     */
    public function reconocimientoAction() {
        $dm = $this->getDocumentManager();
        // Comprobando las propiedades del sistema registradas en la base de datos
        if (!$this->reconocerPropiedadesSistema()) {
            $this->destruirSesionDeUsuario('ERROR_SYSTEM_PROPERTY');
        }
        
        // Comprobando las Propiedades del Sistema
        $instanciasDir = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();

        if (!is_dir($instanciasDir)) {
            $this->get('session')->getFlashBag()->add('warning', 'No existe el directorio especificado para el despliegue de las instancias. Verifique las propiedades del sistema.');
        } elseif (!is_writable($instanciasDir)) {
            $this->get('session')->getFlashBag()->add('warning', 'No es posible acceder al directorio especificado para el despliegue de las instancias. <b>Permiso denegado!!!</b>');
        } else {
            // Obteniendo los archivos registrados en la base de datos
            $archivos = $dm->getRepository('ConfigOrionBundle:archivo')->findAll();
            // Listado de archivos que presentan cambios
            $archivosCambiados = array();
            foreach ($archivos as $archivo) {
                // Obteniendo la ruta del archivo
                $rutaFile = $archivo->getRuta() . '/' . $archivo->getNombre();
                if (strtolower($archivo->getFormato()) != 'null') {
                    $rutaFile .= '.' . strtolower($archivo->getFormato());
                }
                if (!is_writable($rutaFile)) {
                    $this->destruirSesionDeUsuario('ERROR_SYSTEM_RECONOCIMIENTO');
                } else {
                    // Obteniendo el contenido del archivo real
                    $content = file_get_contents($rutaFile);
                    // Comprobando si el archivo registrado en la base de datos y el real son iguales
                    if (strcasecmp(hash('md5', $archivo->getContenido()), hash('md5', $content)) != 0) {
                        $archivosCambiados[] = $archivo;
                    }
                }
            }

            if (!empty($archivosCambiados)) {
                return $this->render('ConfigOrionBundle:usuario:reconocimiento.html.twig', array(
                            'archiosCambiados' => $archivosCambiados,
                ));
            }
        }
        return $this->redirect($this->generateUrl('instancia'));
    }

    /**
     * Destruye la sesion del usuario 
     */
    private function destruirSesionDeUsuario($code) {
        // Destruyendo la sesión de seguridad del sistema
        $this->get('security.context')->setToken(null);
        // Lanzando error del sistema
        throw new HttpException(400, $code);
    }

    /**
     * Verifica que existan todas las propiedades del sistema, en caso de que alguna no exista
     * la crea a partir del archivo principal de configuración (sistema.xml). 
     * El archivo <b>sistema.xml</b> se encuentra ubicado en el directorio <i><b>ExternalClases</b></i>    
     * 
     * @return boolean Devuelve <b>TRUE</b> en caso de éxito, <b>FALSE</b> en caso de error.
     */
    private function reconocerPropiedadesSistema() {
        $mensajeAdd = "";
        // Archivo XSD utilizado para validar la estructura del archivo de configuración del sistema
        $sistemaValidator = '../src/ConfigOrion/ConfigOrionBundle/ExternalClases/sistema.xsd';
        // Verificando que exista el archivo de configuración del sistema
        if (is_file('../src/ConfigOrion/ConfigOrionBundle/ExternalClases/sistema.xml')) {
            $dm = $this->getDocumentManager();
            $xml = new DOMDocument();
            if ($xml->load('../src/ConfigOrion/ConfigOrionBundle/ExternalClases/sistema.xml')) {
                if (@$xml->schemaValidate($sistemaValidator)) {
                    // Obteniendo todas las etiquetas de tipo <propiedad>
                    $propiedadTags = $xml->documentElement->getElementsByTagName('propiedad');
                    for ($i = 0; $i < $propiedadTags->length; $i++) {
                        $propiedad = simplexml_import_dom($propiedadTags->item($i));
                        /// Comprobando que exista la propiedad en la base de datos
                        $nombrePropiedad = $propiedad->nombre->__toString();
                        $isPropiedad = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => $nombrePropiedad));
                        // Registrando la propiedad si no existe en la base de datos
                        if (!$isPropiedad) {
                            $newPropiedad = new sistema();
                            $newPropiedad->setNombre($propiedad->nombre);
                            $newPropiedad->setValor($propiedad->valor);
                            $newPropiedad->setDescripcion($propiedad->descripcion);
                            $dm->persist($newPropiedad);
                            $mensajeAdd .= "<li>$propiedad->nombre: &quot;$propiedad->valor&quot;</li>";
                        }
                    }
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }

        if (!empty($mensajeAdd)) {
            // Persistiendo las propiedades registradas en la base de datos
            $dm->flush();
            $mensaje = 'Las siguientes propiedades del sistema han sido creadas:';
            $mensaje .= '<ul>' . $mensajeAdd . '</ul>';
            $this->get('session')->getFlashBag()->add('info', $mensaje);
        }
        return TRUE;
    }

    /**
     * Sobreescribe el contenido del archivo real con el contenido del 
     * archivo registrado en el sistema.
     * 
     * @param integer $id ID del archivo del sistema
     */
    public function conservarSistemaAction($id) {
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->findOneById($id);
        if ($archivo) {
            // Obteniendo la ruta del archivo
            $rutaFile = $archivo->getRuta() . '/' . $archivo->getNombre();
            if (\strtolower($archivo->getFormato()) != 'null') {
                $rutaFile .= '.' . \strtolower($archivo->getFormato());
            }
            // Codificando el contenido del archivo a UTF-8
            $contenido = html_entity_decode($archivo->getContenido(), null, "UTF-8");
            // Guardando el contenido codificado en el archivo real
            $file = fopen($rutaFile, "w");
            fwrite($file, $contenido);
            fclose($file);
        }
        return $this->redirect($this->generateUrl('reconocimiento'));
    }

    /**
     * Sobreescribe un archivo registrado en el sistema con el contenido del archivo real.
     * Borra todas las etiquetas y propiedades de perfiles asociados.
     * 
     * @param integer $id ID del archivo del sistema
     */
    public function conservarRealAction($id) {
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->findOneById($id);

        if ($archivo) {
            // Obteniendo la ruta del archivo
            $rutaFile = $archivo->getRuta() . '/' . $archivo->getNombre();
            if (\strtolower($archivo->getFormato()) != 'null') {
                $rutaFile .= '.' . \strtolower($archivo->getFormato());
            }
            // Obteniendo el contenido del archivo real
            $content = file_get_contents($rutaFile);

            // Actualizando las modificaciones
            $modificaciones = $archivo->getModificaciones();
            foreach ($modificaciones as $modificacion) {
                $modificacion->setExistePropiedad('FALSE');
                $dm->persist($modificacion);
            }

            // Actualizando las etiquetas
            $etiquetas = $archivo->getEtiquetas();
            foreach ($etiquetas as $etiqueta) {
                $archivo->removeEtiqueta($etiqueta);
                $dm->remove($etiqueta);
            }

            // Actualizando las propiedades de perfil
            $propiedadesPerfiles = $archivo->getPropiedadesPerfiles();
            foreach ($propiedadesPerfiles as $propiedad) {
                $archivo->removePropiedadesPerfile($propiedad);
                $perfil = $dm->getRepository('ConfigOrionBundle:perfil')->getPerfilByPropiedadPerfil($propiedad);
                $perfil->removePropiedadesPerfile($propiedad);
                $dm->remove($propiedad);
                $dm->persist($perfil);
            }

            // Actualizando el contenido del archivo registrado en el sistema
            $archivo->setContenido($content);
            $dm->persist($archivo);
            // Persistiendo todos los cambios realizados en la base de datos
            $dm->flush();
        }
        return $this->redirect($this->generateUrl('reconocimiento'));
    }

    /**
     * Obtiene el DocumentManager
     *
     * @return DocumentManager
     */
    private function getDocumentManager() {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }

}
