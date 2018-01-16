<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\plugin;
use ConfigOrion\ConfigOrionBundle\Form\pluginType;
use ConfigOrion\ConfigOrionBundle\ExternalClases\NutchPlugins;
use ConfigOrion\ConfigOrionBundle\ExternalClases\NutchSite;

/**
 * Clase controladora que se encarga de la gestión de Plugins
 */
class pluginController extends Controller {

    /**
     * Muestra la lista de Plugins registrados en el sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $documents = $dm->getRepository('ConfigOrionBundle:plugin')->findAll();

        return $this->render('ConfigOrionBundle:plugin:index.html.twig', array('documents' => $documents));
    }

    /**
     * Muestra la vista de Registrar Plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction($id_instancia) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);
        $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();

        $rutaBase = $this->getPluginDirectorio($id_instancia);

        if (!$rutaBase) {
            $this->get('session')->getFlashBag()->add('warning', 'Primero debe registrar el archivo <i>"' . $archivo_principal . '"</i>');
            return $this->redirect($this->generateUrl('instancia_admin', array('id' => $id_instancia)));
        } else {
            $document = new plugin();
            $document->setRuta($rutaBase);
            $form = $this->createForm(new pluginType, $document);
            return $this->render('ConfigOrionBundle:plugin:new.html.twig', array(
                        'document' => $document,
                        'instancia' => $instancia,
                        'form' => $form->createView()
            ));
        }
    }

    /**
     * Registra un nuevo Plugin
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction($id_instancia, Request $request) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);

        $document = new plugin();
        $form = $this->createForm(new pluginType(), $document);
        $rutaBase = $this->getPluginDirectorio($id_instancia);

        $form->bind($request);

        $rutaPluginDoc = $dm->getRepository('ConfigOrionBundle:plugin')->findOneBy(array('ruta' => $form->get('ruta')->getData()));
        // Obteniendo directorio de despliegue de instancias
        $directorioInstancias = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();

        if (strncasecmp($directorioInstancias, $form->get('ruta')->getData(), strlen($directorioInstancias)) != 0) { // Verificando que la ruta este dentro del directorio de despliegue de instancias
            $this->get('session')->getFlashBag()->add('danger', 'Ud. no tiene acceso a este directorio. Verifique la ruta entrada!!!');
        } elseif ($rutaPluginDoc != '') {// Verificando que no se use una ruta de plugin 2 veces
            $this->get('session')->getFlashBag()->add('warning', 'El plugin <b>"' . $rutaPluginDoc->getNombre() . '"</b> ya se encuentra registrado en esta instancia.');
        } elseif (substr($form->get('ruta')->getData(), strlen($form->get('ruta')->getData()) - 1, 1) == '/') {  // Verificando que la ruta no termine en /
            $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede terminar con el caracter "/".');
        } elseif (preg_match('/\/\//', $form->get('ruta')->getData()) == 1) {  // Verificando que la ruta no posea dos /
            $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede contener dos caracteres "/" seguidos.');
        } elseif (!is_dir($form->get('ruta')->getData())) {  // Verificando que la ruta existe
            $this->get('session')->getFlashBag()->add('danger', 'La ruta especificada no existe.');
        } elseif (strcmp($instancia->getRuta() . '/', substr($form->get('ruta')->getData(), 0, strlen($instancia->getRuta()) + 1)) != 0) {  // Verificando que el plugin pertenezca a la instancia
            $this->get('session')->getFlashBag()->add('danger', 'Este plugin no se encuentra dentro de la instancia especificada en la ruta!!!');
        } elseif (strcmp($rutaBase . '/', substr($form->get('ruta')->getData(), 0, strlen($rutaBase) + 1)) != 0) {  // Verificando que el plugin pertenezca a la instancia
            $this->get('session')->getFlashBag()->add('danger', 'La ruta del plugin debe ser un directorio dentro de "' . $rutaBase . '"');
        } elseif ($form->isValid()) {

            // Parseando la ruta del plugin
            $rutaPlugin = explode('/', $form->get('ruta')->getData());
            $document->setNombre($rutaPlugin[count($rutaPlugin) - 1]);
            $dm->persist($document);
            $instancia->addPlugin($document);
            $dm->flush();

            $this->desactivarPlugin($instancia->getId(), $document->getId());

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El plugin se ha registrado correctamente.');

            return $this->redirect($this->generateUrl('plugin_show', array('id' => $document->getId(), 'id_instancia' => $instancia->getId())));
        }

        return $this->render('ConfigOrionBundle:plugin:new.html.twig', array(
                    'document' => $document,
                    'instancia' => $instancia,
                    'form' => $form->createView()
        ));
    }

    /**
     * Muestra la vista Ver Datos de un Plugin
     *
     * @param String $id_instancia ID de la instancia
     * @param String $id ID del Plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id_instancia, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);

        $document = $dm->getRepository('ConfigOrionBundle:plugin')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El plugin relacionado no se encontró en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);


        return $this->render('ConfigOrionBundle:plugin:show.html.twig', array(
                    'document' => $document,
                    'instancia' => $instancia,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Plugin
     *
     * @param String $id_instancia ID de la instancia
     * @param String $id ID del Plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id_instancia, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);

        $document = $dm->getRepository('ConfigOrionBundle:plugin')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El plugin relacionado no se encontró en la base de datos.');
        }

        $editForm = $this->createForm(new pluginType(), $document);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:plugin:edit.html.twig', array(
                    'document' => $document,
                    'instancia' => $instancia,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los Datos de un Plugin
     *
     * @param String $id_instancia ID de la instancia
     * @param String $id ID del Plugin
     * @param Request $request Datos de la solicitud
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction($id_instancia, Request $request, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);

        $document = $dm->getRepository('ConfigOrionBundle:plugin')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El plugin relacionado no se encontró en la base de datos.');
        }

        $rutaBase = $this->getPluginDirectorio($id_instancia);

        $deleteForm = $this->createDeleteForm($id);
        // Ruta anterior
        $pluginAnterior = clone $document;

        $editForm = $this->createForm(new pluginType(), $document);
        $editForm->bind($request);

        // Parseando la ruta del plugin
        $rutaPlugin = explode('/', $editForm->get('ruta')->getData());

        $rutaPluginDoc = $dm->getRepository('ConfigOrionBundle:plugin')->findOneBy(array('ruta' => $editForm->get('ruta')->getData()));

        // Obteniendo directorio de despliegue de instancias
        $directorioInstancias = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();

        if (strncasecmp($directorioInstancias, $editForm->get('ruta')->getData(), strlen($directorioInstancias)) != 0) { // Verificando que la ruta este dentro del directorio de despliegue de instancias
            $this->get('session')->getFlashBag()->add('danger', 'Ud. no tiene acceso a este directorio. Verifique la ruta entrada!!!');
        } elseif ($document->getNombre() != $rutaPlugin[count($rutaPlugin) - 1]) {// Verificando que el nombre sea igual al directorio padre
            $this->get('session')->getFlashBag()->add('danger', 'El nombre del plugin no coincide con el directorio padre especificado en la ruta!!!');
        } elseif ($rutaPluginDoc != '' && $pluginAnterior->getRuta() != $rutaPluginDoc->getRuta()) {// Verificando que no se use una ruta de plugin 2 veces
            $this->get('session')->getFlashBag()->add('warning', 'La ruta especificada est&aacute; siendo usada por el plugin <b>"' . $rutaPluginDoc->getNombre() . '"</b>. Se recomienda escoger otra ruta.');
        } elseif (substr($editForm->get('ruta')->getData(), strlen($editForm->get('ruta')->getData()) - 1, 1) == '/') {  // Verificando que la ruta no termine en /
            $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede terminar con el caracter "/".');
        } elseif (preg_match('/\/\//', $editForm->get('ruta')->getData()) == 1) {  // Verificando que la ruta no posea dos /
            $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede contener dos caracteres "/" seguidos.');
        } elseif (!is_dir($editForm->get('ruta')->getData())) {  // Verificando que la ruta existe
            $this->get('session')->getFlashBag()->add('danger', 'La ruta especificada no existe.');
        } elseif (strcmp($instancia->getRuta() . '/', substr($editForm->get('ruta')->getData(), 0, strlen($instancia->getRuta()) + 1)) != 0) {  // Verificando que el plugin pertenezca a la instancia
            $this->get('session')->getFlashBag()->add('danger', 'Este plugin no se encuentra dentro de la instancia especificada en la ruta!!!');
        } elseif (strcmp($rutaBase . '/', substr($editForm->get('ruta')->getData(), 0, strlen($rutaBase) + 1)) != 0) {  // Verificando que el plugin pertenezca a la instancia
            $this->get('session')->getFlashBag()->add('danger', 'La ruta del plugin debe ser un directorio dentro de "' . $rutaBase . '"');
        } elseif ($editForm->isValid()) {
            // Actualizando las rutas de los archivos si se modifica la ruta actual
            if ($editForm->get('ruta')->getData() != $pluginAnterior->getRuta()) {
                $archivos = $document->getArchivos();
                foreach ($archivos as $archivo) {
                    $newRuta = str_replace($pluginAnterior->getRuta(), $editForm->get('ruta')->getData(), $archivo->getRuta());
                    $archivo->setRuta($newRuta);
                    $dm->persist($archivo);
                }
            }

            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El plugin se ha editado correctamente.');
            return $this->redirect($this->generateUrl('plugin_show', array('id' => $id, 'id_instancia' => $instancia->getId())));
        }

        return $this->render('ConfigOrionBundle:plugin:edit.html.twig', array(
                    'document' => $document,
                    'instancia' => $instancia,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Registra automáticamente los Plugins detectados en el archivo principal de
     * configuración de Nutch (property: plugins.includes)
     * 
     * @param String $id_instancia ID de la instancia     
     */
    public function detectarAction($id_instancia) {
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);
        $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();
        $archivos = $instancia->getArchivos();

        // Buscando el archivo nutch-site,xml
        $encontrado = FALSE;
        $size = count($archivos);
        $nombre_archivo = "";
        for ($i = 0; $i < $size && !$encontrado; $i++) {
            $archivo = $archivos[$i];
            $nombre_archivo = $archivo->getNombre() . '.' . strtolower($archivo->getFormato());
            if ($nombre_archivo == $archivo_principal) {
                $encontrado = TRUE;
            }
        }
        if ($encontrado) {
            $rutaArchivo = $archivo->getRuta() . '/' . $archivo_principal;
            if (!is_writable($rutaArchivo)) {
                $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error inesperado mientras se intentaba detectar los plugins de configuración.');
                $this->get('session')->getFlashBag()->add('warning', 'Verifique que exista el archivo principal de configuración y tenga los permisos necesarios.');
            } else {
                $plugins_error = $this->detectarPlugins($archivo->getContenido(), $instancia);
                if (!$plugins_error)
                    $this->get('session')->getFlashBag()->add('success', "Todos los plugins han sido registrados correctamente.");
                else {
                    $output = $plugins_error[0];
                    while ($error = next($plugins_error)) {
                        $output .= ' | ' . $error;
                    }
                    $this->get('session')->getFlashBag()->add('warning', "Algunos plugins no pudieron ser registrados correctamente: ($output)");
                }
            }
        } else {
            $this->get('session')->getFlashBag()->add('warning', "Primero debe registrar el archivo <i>&quot;$archivo_principal&quot;</i>");
        }

        return $this->redirect($this->generateUrl('instancia_admin', array('id' => $id_instancia)));
    }

    /**
     * Registra automáticamente los Plugins detectados en el archivo principal de
     * configuración de Nutch (property: plugins.includes)
     * 
     * @param String $archivo Contenido del archivo principal de configuración
     * @param Document $instancia Instancia de la clase de tipo Document "instancia"
     * @return array Retorna un arreglo con el nombre de los plugins que no pudieron ser registrados
     * producto de algún error
     */
    private function detectarPlugins($archivo, $instancia) {
        $nutch_site = new NutchSite($archivo);
        $nutchPlugins = new NutchPlugins();
        $dm = $this->getDocumentManager();
        $instancia_ruta = $instancia->getRuta();
        $instancia_plugins = $instancia->getPlugins();
        $plugins_configuracion_property = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'plugins_configuracion'))->getValor();
        $plugins_directory_property = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'plugins_directorio'))->getValor();

        $plugins_configuracion = $nutch_site->getPropertyPorNombre($plugins_configuracion_property);
        $plugins_directory = $nutch_site->getPropertyPorNombre($plugins_directory_property);

        $nutchPlugins->extraerNutchPlugins($plugins_configuracion->getValue());
        $plugins = $nutchPlugins->getPlugins();
        $plugins_error = array();

        foreach ($plugins as $plugin_nombre) {
            $plugin_ruta = $instancia_ruta . '/' . $plugins_directory->getValue() . '/' . $plugin_nombre;
            if (is_dir($plugin_ruta)) {
                // Comprobando si el plugin ya fue registrado anteriormente en la instancia
                if (!$this->isPlugin($instancia_plugins, $plugin_nombre)) {
                    $plugin_nuevo = new plugin();
                    $plugin_nuevo->setNombre($plugin_nombre);
                    $plugin_nuevo->setRuta($plugin_ruta);
                    $plugin_nuevo->setActivado(true);
                    $dm->persist($plugin_nuevo);
                    $instancia->addPlugin($plugin_nuevo);
                    $dm->persist($instancia);
                    $dm->flush();
                }
            } else {
                $plugins_error[] = $plugin_nombre;
            }
        }
        return $plugins_error;
    }

    /**
     * Verifica si un Plugin forma parte de una lista de Plugins
     * 
     * @param array $plugins Lista de plugins
     * @param String $nombre Nombre del plugin a verificar
     * @return boolean Retorna <b>TRUE</b> en caso de que exista el plugin, 
     * <b>FALSE</b> en caso de no encontrarse en la lista.
     */
    private function isPlugin($plugins, $nombre) {
        foreach ($plugins as $plugin) {
            if ($plugin->getNombre() == $nombre)
                return TRUE;
        }
        return FALSE;
    }

    /**
     * Elimina un Plugin
     *
     * @param String $id_instancia ID de la instancia
     * @param String $id ID del Plugin
     * @param Request $request Datos de la solicitud
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction($id_instancia, Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);

        if ($form->isValid()) {
            $document = $dm->getRepository('ConfigOrionBundle:plugin')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('El plugin relacionado no se encontró en la base de datos.');
            }

            $instancia->removePlugin($document);
            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'El plugin se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl('instancia_admin', array('id' => $instancia->getId())));
    }

    /**
     * Muestra la vista de Administración de un Plugin
     *
     * @param string $id ID del Plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function adminAction($id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:plugin')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El plugin relacionado no se encontró en la base de datos.');
        }

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($document);

        $archivos = $document->getArchivos();

        $deleteForm = $this->createDeleteForm($id);


        return $this->render('ConfigOrionBundle:plugin:admin.html.twig', array(
                    'instancia' => $instancia,
                    'document' => $document,
                    'archivos' => $archivos,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Desactiva un Plugin
     * 
     * @param String $id_instancia ID de la instancia
     * @param String $id ID del Plugin    
     */
    public function desactivarAction($instancia, $id_plugin) {
        $desactivado = $this->desactivarPlugin($instancia, $id_plugin);
        if ($desactivado) {
            $this->get('session')->getFlashBag()->add('success', 'El plugin ha sido desactivado correctamente.');
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error inesperado mientras se intentaba desactivar el plugin.');
        }
        return $this->redirect($this->generateUrl('instancia_admin', array('id' => $instancia)));
    }

    /**
     * Desactiva un Plugin de una Instancia
     * 
     * @param String $instancia ID de la instancia
     * @param String $id_plugin ID del Plugin 
     * @return boolean Retorna <b>TRUE</b> si el plugin fue desactivado correctamente, 
     *  <b>FALSE</b> en caso contrario
     */
    private function desactivarPlugin($instancia, $id_plugin) {
        $dm = $this->getDocumentManager();
        $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();
        $archivos_instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($instancia)->getArchivos();

        // Buscando el archivo nutch-site en la instancia correspondiente
        $encontrado = FALSE;
        $size = count($archivos_instancia);

        for ($i = 0; $i < $size && !$encontrado; $i++) {
            $archivo = $archivos_instancia[$i];
            $nombreArchivo = $archivo->getNombre() . '.' . strtolower($archivo->getFormato());
            if ($nombreArchivo == $archivo_principal) {
                $encontrado = TRUE;
            }
        }

        if ($encontrado) {
            $rutaArchivo = $archivo->getRuta() . '/' . $archivo_principal;
            if (!is_writable($rutaArchivo)) {
                $this->get('session')->getFlashBag()->add('warning', 'Verifique que exista el archivo principal de configuración y tenga los permisos necesarios.');
            } else {
                $plugin = $dm->getRepository('ConfigOrionBundle:plugin')->find($id_plugin);
                $plugins_configuracion = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'plugins_configuracion'))->getValor();
                $nutch_site = new NutchSite($archivo->getContenido());
                $plugins_includes = $nutch_site->getPropertyPorNombre($plugins_configuracion);
                if ($plugins_includes) {
                    // Desactivando el plugin                               
                    $nutchPlugins = new NutchPlugins();
                    $nutchPlugins->extraerNutchPlugins($plugins_includes->getValue());
                    $nutchPlugins->eliminarPlugin($plugin->getNombre());
                    $plugin->setActivado(false);
                    $plugins_includes->setValue($nutchPlugins->pluginsToRegex());
                    $nutch_site->actualizarProperty($plugins_includes);
                    $nutch_site->guardarContenidoXML($archivo->getRuta() . '/' . $archivo_principal);
                    $archivo->setContenido($nutch_site->getContenidoXML());
                    $dm->persist($plugin);
                    $dm->persist($archivo);
                    $dm->flush();
                    return TRUE;
                }
            }
            return FALSE;
        }
    }

    /**
     * Desactiva un Plugin de una Instancia
     * 
     * @param String $instancia ID de la instancia
     * @param String $id_plugin ID del Plugin  
     */
    public function activarAction($instancia, $id_plugin) {
        $dm = $this->getDocumentManager();
        $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();
        $archivos_instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($instancia)->getArchivos();

        // Buscando el archivo nutch-site en la instancia correspondiente
        $encontrado = FALSE;
        $size = count($archivos_instancia);

        for ($i = 0; $i < $size && !$encontrado; $i++) {
            $archivo = $archivos_instancia[$i];
            $nombreArchivo = $archivo->getNombre() . '.' . strtolower($archivo->getFormato());
            if ($nombreArchivo == $archivo_principal) {
                $encontrado = TRUE;
            }
        }

        if ($encontrado) {
            $rutaArchivo = $archivo->getRuta() . '/' . $archivo_principal;
            if (!is_writable($rutaArchivo)) {
                $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error inesperado mientras se intentaba activar el plugin.');
                $this->get('session')->getFlashBag()->add('warning', 'Verifique que exista el archivo principal de configuración y tenga los permisos necesarios.');
            } else {
                $plugin = $dm->getRepository('ConfigOrionBundle:plugin')->find($id_plugin);
                $plugins_configuracion = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'plugins_configuracion'))->getValor();
                $nutch_site = new NutchSite($archivo->getContenido());
                $plugins_includes = $nutch_site->getPropertyPorNombre($plugins_configuracion);
                if ($plugins_includes) {
                    //Desactivando el plugin                               
                    $nutchPlugins = new NutchPlugins();
                    $nutchPlugins->extraerNutchPlugins($plugins_includes->getValue());
                    $nutchPlugins->adicionarPlugin($plugin->getNombre());
                    $plugin->setActivado(true);
                    $plugins_includes->setValue($nutchPlugins->pluginsToRegex());
                    $nutch_site->actualizarProperty($plugins_includes);
                    $nutch_site->guardarContenidoXML($archivo->getRuta() . '/' . $archivo_principal);
                    $archivo->setContenido($nutch_site->getContenidoXML());
                    $dm->persist($plugin);
                    $dm->persist($archivo);
                    $dm->flush();
                    $this->get('session')->getFlashBag()->add('success', 'El plugin ha sido activado correctamente.');
                } else {
                    $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error inesperado mientras se intentaba desactivar el plugin.');
                }
            }
        }

        return $this->redirect($this->generateUrl('instancia_admin', array('id' => $instancia)));
    }

    /**
     * Crea el formulario de eliminar Plugin
     * 
     * @param String $id ID de la Etiqueta    
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
                        ->add('id', 'hidden')
                        ->getForm()
        ;
    }

    /**
     * Obtiene el DocumentManager
     *
     * @return DocumentManager
     */
    private function getDocumentManager() {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }

    private function getPluginDirectorio($id_instancia) {
        $dm = $this->getDocumentManager();
        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);
        $pluginDirectorio = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'plugins_directorio'))->getValor();

        if (!$instancia) {
            throw $this->createNotFoundException('La instancia relacionada a este plugin no existe en la base de datos.');
        }

        $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();
        $archivos = $instancia->getArchivos();

        // Buscando el archivo nutch-site,xml
        $encontrado = FALSE;
        $size = count($archivos);
        for ($i = 0; $i < $size && !$encontrado; $i++) {
            $archivo = $archivos[$i];
            $nombre_archivo = $archivo->getNombre() . '.' . strtolower($archivo->getFormato());
            if ($nombre_archivo == $archivo_principal) {
                $encontrado = TRUE;
                $contenido = $archivo->getContenido();
            }
        }

        if ($encontrado) {
            $nutch_site = new NutchSite($contenido);
            $rutaPlugins = $nutch_site->getPropertyPorNombre($pluginDirectorio);
            $rutaBase = $instancia->getRuta() . '/' . $rutaPlugins->getValue();
            return $rutaBase;
        } else
            return FALSE;
    }

}
