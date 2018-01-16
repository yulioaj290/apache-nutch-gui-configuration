<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\modificacion;
use ConfigOrion\ConfigOrionBundle\Form\modificacionType;
use ConfigOrion\ConfigOrionBundle\Form\archivarModificacionType;
use ConfigOrion\ConfigOrionBundle\ExternalClases\GenerarXML;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clase controladora que se encarga de la gestión de modificaciones
 */
class modificacionController extends Controller {

    /**
     * Muestra la lista de modificaciones registradas en el sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $documents = $dm->getRepository('ConfigOrionBundle:modificacion')->findAll();

        return $this->render('ConfigOrionBundle:modificacion:index.html.twig', array('documents' => $documents));
    }

    /**
     * Muestra la vista de Crear Modificación
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction($id_archivo) {
        $document = new modificacion();
        $form = $this->createForm(new modificacionType(), $document);

        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        return $this->render('ConfigOrionBundle:modificacion:new.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'form' => $form->createView()
        ));
    }

    /**
     * Registra una nueva Modificación
     *
     * @param Request $request Datos de la solicitud
     * @param String $id_archivo ID del archivo de configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction($id_archivo, Request $request) {
        $document = new modificacion();
        $form = $this->createForm(new modificacionType(), $document);
        $form->bind($request);

        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        if (!$archivo) {
            throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
        }

        if ($form->isValid()) {
            $dm->persist($document);
            $archivo->addModificacione($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El cambio se ha registrado correctamente.');
            return $this->redirect($this->generateUrl('modificacion_show', array('id_archivo' => $id_archivo, 'id' => $document->getId())));
        }

        return $this->render('ConfigOrionBundle:modificacion:new.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'form' => $form->createView()
        ));
    }

    /**
     * Muestra la vista de Ver Datos de Modificación
     *
     * @param String $id ID de la Modificación
     * @param String $id_archivo ID del archivo de configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id_archivo, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        $document = $dm->getRepository('ConfigOrionBundle:modificacion')->find($id);

        if (!$archivo) {
            throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
        }

        if (!$document) {
            throw $this->createNotFoundException('El registro de la modificación ya no se encuentra disponible en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);

        $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($archivo);
        $tipo = 'instancia';

        if (!$instanciaItem) {
            $plugin = $dm->getRepository('ConfigOrionBundle:plugin')->getPluginByArchivo($archivo);
            $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($plugin);
            $tipo = 'plugin';
            return $this->render('ConfigOrionBundle:modificacion:show.html.twig', array(
                        'document' => $document,
                        'archivo' => $archivo,
                        'tipo' => $tipo,
                        'plugin_id' => $plugin->getId(),
                        'instancia_item' => $instanciaItem,
                        'delete_form' => $deleteForm->createView(),));
        }

        return $this->render('ConfigOrionBundle:modificacion:show.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'tipo' => $tipo,
                    'instancia_item' => $instanciaItem,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Modificación
     *
     * @param String $id ID de la Modificación
     * @param String $id_archivo ID del archivo de configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id_archivo, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        $document = $dm->getRepository('ConfigOrionBundle:modificacion')->find($id);

        if (!$archivo) {
            throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
        }

        if (!$document) {
            throw $this->createNotFoundException('El registro de la modificación ya no se encuentra disponible en la base de datos.');
        }

        $editForm = $this->createForm(new modificacionType(), $document);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:modificacion:edit.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los datos de una Modificación
     *
     * @param Request $request Datos de la solicitud
     * @param String $id ID de la Modificación
     * @param String $id_archivo ID del archivo de configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction($id_archivo, Request $request, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        $document = $dm->getRepository('ConfigOrionBundle:modificacion')->find($id);

        if (!$archivo) {
            throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
        }

        if (!$document) {
            throw $this->createNotFoundException('El registro de la modificación ya no se encuentra disponible en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new modificacionType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El regitro de cambio se ha editado correctamente.');
            return $this->redirect($this->generateUrl('modificacion_edit', array('id_archivo' => $id_archivo, 'id' => $id)));
        }

        return $this->render('ConfigOrionBundle:modificacion:edit.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Elimina los datos de una Modificación
     *
     * @param Request $request The request object
     * @param String $id ID de la Modificación
     * @param String $id_archivo ID del archivo de configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction($id_archivo, Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        if ($form->isValid()) {
            $document = $dm->getRepository('ConfigOrionBundle:modificacion')->find($id);

            if (!$archivo) {
                throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
            }

            if (!$document) {
                throw $this->createNotFoundException('El registro de la modificación ya no se encuentra disponible en la base de datos.');
            }

            $archivo->removeModificacione($document);
            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'El registro de cambio se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl('archivo_admin', array('id' => $archivo->getId())));
    }

    /**
     * Exporta todas las Modificaciones registradas en el sistema
     * 
     * @param Request $request Datos de la solicitud 
     */
    public function archivarAction(Request $request) {
        $formArchivar = $this->createForm(new archivarModificacionType());
        $formArchivar->bind($request);

        if ($formArchivar->isValid()) {
            if ($formArchivar->get('dateModificacion')->getData() != '' &&
                    !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $formArchivar->get('dateModificacion')->getData())) {
                $this->get('session')->getFlashBag()->add('danger', 'El formato de la fecha es incorrecto. Debe introducir una fecha válida.');
                return $this->redirect($this->generateUrl('instancia'));
            } elseif ($formArchivar->get('dateModificacion')->getData() != '') {
                //Creando documento XML para archivar los datos
                $XMLArchivador = new GenerarXML();

                // Inicializando el Administrador de Documentos
                $dm = $this->getDocumentManager();
                $instancias = $dm->getRepository('ConfigOrionBundle:instancia')->findAll();

                // Creando la Raiz
                $tagInstancias = $XMLArchivador->crearTag('instancias');

                foreach ($instancias as $instancia) {
                    $attrsI = array('nombre', 'ruta');
                    $valoresI = array($instancia->getNombre(), $instancia->getRuta());
                    $tagInstancia = $XMLArchivador->crearTagConValores('instancia', $attrsI, $valoresI, $tagInstancias);
                    $archivos = $instancia->getArchivos();
                    $plugins = $instancia->getPlugins();
                    foreach ($plugins as $plugin) {
                        $attrsP = array('nombre', 'ruta');
                        $valoresP = array($plugin->getNombre(), $plugin->getRuta());
                        $tagPlugin = $XMLArchivador->crearTagConValores('plugin', $attrsP, $valoresP, $tagInstancia);
                        $archivosP = $plugin->getArchivos();
                        foreach ($archivosP as $archivo) {
                            $attrsA = array('nombre', 'ruta');
                            $valoresA = array($archivo->getNombre(), $archivo->getRuta());
                            $tagArchivo = $XMLArchivador->crearTagConValores('archivo', $attrsA, $valoresA, $tagPlugin);
                            $modifiaciones = $archivo->getModificaciones();
                            foreach ($modifiaciones as $modificacion) {
                                $dateModificacion = \DateTime::createFromFormat('d/m/Y', $formArchivar->get('dateModificacion')->getData());
                                if ($dateModificacion > $modificacion->getFecha()) {
                                    $tagModificacion = $XMLArchivador->crearTag('modificacion', $tagArchivo);
                                    // Datos de la modificacion
                                    $tagFecha = $XMLArchivador->crearTag('fecha', $tagModificacion);
                                    $XMLArchivador->crearTexto($modificacion->getFecha()->format('d/m/Y'), $tagFecha);
                                    $tagPropiedad = $XMLArchivador->crearTag('propiedad', $tagModificacion);
                                    $XMLArchivador->crearTexto($modificacion->getPropiedad(), $tagPropiedad);
                                    $tagRutaPropiedad = $XMLArchivador->crearTag('rutaPropiedad', $tagModificacion);
                                    $XMLArchivador->crearTexto($modificacion->getRutaPropiedad(), $tagRutaPropiedad);
                                    $tagValorAnterior = $XMLArchivador->crearTag('valorAnterior', $tagModificacion);
                                    $XMLArchivador->crearTexto($modificacion->getValorAnterior(), $tagValorAnterior);
                                    $tagValorActual = $XMLArchivador->crearTag('valorActual', $tagModificacion);
                                    $XMLArchivador->crearTexto($modificacion->getValorActual(), $tagValorActual);
                                    $tagTipoMod = $XMLArchivador->crearTag('tipoModificacion', $tagModificacion);
                                    $XMLArchivador->crearTexto($modificacion->getTipoModificacion(), $tagTipoMod);
                                    $tagDescripcion = $XMLArchivador->crearTag('descripcion', $tagModificacion);
                                    $XMLArchivador->crearTexto($modificacion->getDescripcion(), $tagDescripcion);

                                    //Eliminando la modificacion del sistema
                                    $archivo->removeModificacione($modificacion);
                                    $dm->remove($modificacion);
                                }
                            }
                        }
                    }
                    foreach ($archivos as $archivo) {
                        $attrsA = array('nombre', 'ruta');
                        $valoresA = array($archivo->getNombre(), $archivo->getRuta());
                        $tagArchivo = $XMLArchivador->crearTagConValores('archivo', $attrsA, $valoresA, $tagInstancia);
                        $modifiaciones = $archivo->getModificaciones();
                        foreach ($modifiaciones as $modificacion) {
                            $dateModificacion = \DateTime::createFromFormat('d/m/Y', $formArchivar->get('dateModificacion')->getData());
                            if ($dateModificacion > $modificacion->getFecha()) {
                                $tagModificacion = $XMLArchivador->crearTag('modificacion', $tagArchivo);
                                // Datos de la modificacion
                                $tagFecha = $XMLArchivador->crearTag('fecha', $tagModificacion);
                                $XMLArchivador->crearTexto($modificacion->getFecha()->format('d/m/Y'), $tagFecha);
                                $tagPropiedad = $XMLArchivador->crearTag('propiedad', $tagModificacion);
                                $XMLArchivador->crearTexto($modificacion->getPropiedad(), $tagPropiedad);
                                $tagRutaPropiedad = $XMLArchivador->crearTag('rutaPropiedad', $tagModificacion);
                                $XMLArchivador->crearTexto($modificacion->getRutaPropiedad(), $tagRutaPropiedad);
                                $tagValorAnterior = $XMLArchivador->crearTag('valorAnterior', $tagModificacion);
                                $XMLArchivador->crearTexto($modificacion->getValorAnterior(), $tagValorAnterior);
                                $tagValorActual = $XMLArchivador->crearTag('valorActual', $tagModificacion);
                                $XMLArchivador->crearTexto($modificacion->getValorActual(), $tagValorActual);
                                $tagTipoMod = $XMLArchivador->crearTag('tipoModificacion', $tagModificacion);
                                $XMLArchivador->crearTexto($modificacion->getTipoModificacion(), $tagTipoMod);
                                $tagDescripcion = $XMLArchivador->crearTag('descripcion', $tagModificacion);
                                $XMLArchivador->crearTexto($modificacion->getDescripcion(), $tagDescripcion);

                                //Eliminando la modificacion del sistema
                                $archivo->removeModificacione($modificacion);
                                $dm->remove($modificacion);
                            }
                        }
                    }
                    $dm->persist($instancia);
                }
                //Persistiendo cambios
                $dm->persist($instancia);
                $dm->flush();

                $XMLArchivador->asignarRaiz($tagInstancias);
                $XMLArchivador->guardarXML('modificaciones');

                // Decodificando caracteres
                $newContent = html_entity_decode(file_get_contents('modificaciones.xml'), null, "UTF-8");
                // Guardando contenido decodificado
                $file = fopen('modificaciones.xml', "w");
                fwrite($file, $newContent);
                fclose($file);
                $r = Response::create(file_get_contents('modificaciones.xml'), 200, array("Content-Type" => "application/xml",
                            "content-disposition" => "attachment;filename=modificaciones.xml"));
                unlink('modificaciones.xml');
                return $r;
                $this->get('session')->getFlashBag()->add('success', 'Las modificaciones anteriores a la fecha "' . $formArchivar->get('dateModificacion')->getData() . '" se han archivado en "ConfigOrion/web/modificaciones.xml".');
            } else {
                $this->get('session')->getFlashBag()->add('warning', 'Para archivar modificaciones debe especificar una fecha de antiguedad.');
            }
        }
        return $this->redirect($this->generateUrl('instancia'));
    }

    /**
     * Exporta todas las Modificaciones de un archivo registradas en el sistema 
     * 
     * @param Request $request Datos de la solicitud 
     */
    public function archivarPorArchivoAction($id_archivo, Request $request) {
        $formArchivar = $this->createForm(new archivarModificacionType());
        $formArchivar->bind($request);

        if ($formArchivar->isValid()) {
            if ($formArchivar->get('dateModificacion')->getData() != '' &&
                    !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $formArchivar->get('dateModificacion')->getData())) {
                $this->get('session')->getFlashBag()->add('danger', 'El formato de la fecha es incorrecto. Debe introducir una fecha válida.');
                return $this->redirect($this->generateUrl('archivo_admin', array('id' => $id_archivo)));
            } elseif ($formArchivar->get('dateModificacion')->getData() != '') {
                //Creando documento XML para archivar los datos
                $XMLArchivador = new GenerarXML();

                // Inicializando el Administrador de Documentos
                $dm = $this->getDocumentManager();
                $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

                // Creando raiz
                $attrsA = array('nombre', 'ruta');
                $valoresA = array($archivo->getNombre(), $archivo->getRuta());
                $tagArchivo = $XMLArchivador->crearTagConValores('archivo', $attrsA, $valoresA);
                $modifiaciones = $archivo->getModificaciones();
                foreach ($modifiaciones as $modificacion) {
                    $dateModificacion = \DateTime::createFromFormat('d/m/Y', $formArchivar->get('dateModificacion')->getData());
                    if ($dateModificacion > $modificacion->getFecha()) {
                        $tagModificacion = $XMLArchivador->crearTag('modificacion', $tagArchivo);
                        // Datos de la modificacion
                        $tagFecha = $XMLArchivador->crearTag('fecha', $tagModificacion);
                        $XMLArchivador->crearTexto($modificacion->getFecha()->format('d/m/Y'), $tagFecha);
                        $tagPropiedad = $XMLArchivador->crearTag('propiedad', $tagModificacion);
                        $XMLArchivador->crearTexto($modificacion->getPropiedad(), $tagPropiedad);
                        $tagRutaPropiedad = $XMLArchivador->crearTag('rutaPropiedad', $tagModificacion);
                        $XMLArchivador->crearTexto($modificacion->getRutaPropiedad(), $tagRutaPropiedad);
                        $tagValorAnterior = $XMLArchivador->crearTag('valorAnterior', $tagModificacion);
                        $XMLArchivador->crearTexto($modificacion->getValorAnterior(), $tagValorAnterior);
                        $tagValorActual = $XMLArchivador->crearTag('valorActual', $tagModificacion);
                        $XMLArchivador->crearTexto($modificacion->getValorActual(), $tagValorActual);
                        $tagTipoMod = $XMLArchivador->crearTag('tipoModificacion', $tagModificacion);
                        $XMLArchivador->crearTexto($modificacion->getTipoModificacion(), $tagTipoMod);
                        $tagDescripcion = $XMLArchivador->crearTag('descripcion', $tagModificacion);
                        $XMLArchivador->crearTexto($modificacion->getDescripcion(), $tagDescripcion);

                        //Eliminando la modificacion del sistema
                        $archivo->removeModificacione($modificacion);
                        $dm->remove($modificacion);
                    }
                }
                //Persistiendo cambios
                $dm->persist($archivo);
                $dm->flush();

                $XMLArchivador->asignarRaiz($tagArchivo);
                $XMLArchivador->guardarXML('modificaciones_' . $archivo->getNombre());

                // Decodificando caracteres
                $newContent = html_entity_decode(file_get_contents('modificaciones_' . $archivo->getNombre() . '.xml'), null, "UTF-8");
                // Guardando contenido decodificado
                $file = fopen('modificaciones_' . $archivo->getNombre() . '.xml', "w");
                fwrite($file, $newContent);
                fclose($file);
                $r = Response::create(file_get_contents('modificaciones_' . $archivo->getNombre() . '.xml'), 200, array("Content-Type" => "application/xml",
                            "content-disposition" => "attachment;filename=modificaciones_" . $archivo->getNombre() . ".xml"));
                unlink('modificaciones_' . $archivo->getNombre() . '.xml');
                return $r;
                $this->get('session')->getFlashBag()->add('success', 'Las modificaciones anteriores a la fecha "' . $formArchivar->get('dateModificacion')->getData() . '" se han archivado en "ConfigOrion/web/modificaciones.xml".');
            } else {
                $this->get('session')->getFlashBag()->add('warning', 'Para archivar modificaciones debe especificar una fecha de antiguedad.');
            }
        }
        return $this->redirect($this->generateUrl('archivo_admin', array('id' => $id_archivo)));
    }

    /**
     * Crea el formulario de Eliminar Modificación
     * 
     * @param String $id ID de la Modificación
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

}
