<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\modificacion;
use ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil;
use ConfigOrion\ConfigOrionBundle\Form\propiedadPerfilType;

/**
 * Clase controladora que se encarga de la gestión de Propiedades de un Perfil 
 * de Configuración
 */
class propiedadPerfilController extends Controller {

    /**
     * Muestra la vista de Crear Propiedad de Perfil
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $document = new propiedadPerfil();
        $form = $this->createForm(new propiedadPerfilType(), $document);

        return $this->render('ConfigOrionBundle:propiedadPerfil:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Crea una Propiedad de Perfil 
     *
     * @param Request $request Datos de la solicitud
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $document = new propiedadPerfil();
        $form = $this->createForm(new propiedadPerfilType(), $document);
        $form->bind($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $dm->persist($document);
            $dm->flush();

            return $this->redirect($this->generateUrl('propiedadperfil_show', array('id' => $document->getId())));
        }

        return $this->render('ConfigOrionBundle:propiedadPerfil:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Muestra la vista Ver Datos de Propiedad de Perfil
     *
     * @param String $id The document ID de la Propiedad de Perfil
     * @param String $perfil ID del Perfil
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($perfil, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:propiedadPerfil')->find($id);

        if (!$document || !$this->isPerfil($perfil)) {
            throw $this->createNotFoundException('No existe la propiedad de perfil solicitada.');
        }

        $deleteForm = $this->createDeleteForm($perfil, $id);


        return $this->render('ConfigOrionBundle:propiedadPerfil:show.html.twig', array(
                    'document' => $document,
                    'id_perfil' => $perfil,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Propiedad de Perfil
     *
     * @param String $id The document ID de la Propiedad de Perfil
     * @param String $perfil ID del Perfil
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($perfil, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:propiedadPerfil')->find($id);

        if (!$document || !$this->isPerfil($perfil)) {
            throw $this->createNotFoundException('No existe la propiedad de perfil solicitada.');
        }

        $editForm = $this->createForm(new propiedadPerfilType(), $document);
        $deleteForm = $this->createDeleteForm($perfil, $id);

        return $this->render('ConfigOrionBundle:propiedadPerfil:edit.html.twig', array(
                    'document' => $document,
                    'id_perfil' => $perfil,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Verifica si un Perfil existe en la base de datos
     * 
     * @param String $perfil ID del Perfil
     * @return boolean Retorna <b>TRUE</b> si existe el Perfil, <b>FALSE</b> en
     * caso contrario.
     */
    private function isPerfil($perfil) {
        $dm = $this->getDocumentManager();
        $found = $dm->getRepository('ConfigOrionBundle:perfil')->find($perfil);
        if ($found)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Aplica una Propiedad de Perfil
     * 
     * @param String $propiedad ID de la Propiedad de Perfil
     * @return type
     */
    public function aplicarAction($propiedad) {
        $result = $this->aplicarPropiedad($propiedad);
        if ($result) {
            $this->get('session')->getFlashBag()->add('success', 'La propiedad se ha aplicado correctamente.');
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'La propiedad no se ha podido aplicar correctamente.');
        }
        return $this->redirect($this->generateUrl('perfil'));
    }

    /**
     * Aplica una Propiedad de Perfil
     * 
     * @param String $propiedad ID de la Propiedad de Perfil
     * @return boolean Retorna <b>TRUE</b> si la Propiedad de Perfil fue aplicada correctamente,
     *  <b>FALSE</b> en caso contrario.   
     */
    private function aplicarPropiedad($propiedad) {
        $dm = $this->getDocumentManager();
        $propiedad = $dm->getRepository('ConfigOrionBundle:propiedadPerfil')->find($propiedad);
        if (!$propiedad) {
            throw $this->createNotFoundException("No existe la propiedad de perfil solicitada.");
        } else {
            $archivo = $propiedad->getArchivoId();
            $contenido = $archivo->getContenido();

            // Obteniendo ruta del archivo
            $extension = '';
            $rutaFile = $archivo->getRuta() . '/' . $archivo->getNombre();
            if (\strtolower($archivo->getFormato()) != 'null') {
                $extension = '.' . strtolower($archivo->getFormato());
                $rutaFile .= $extension;
            }

            $archivoNombre = $archivo->getNombre() . $extension;

            // Verificando que el archivo existe
            if (!is_writable($rutaFile)) {
                $this->get('session')->getFlashBag()->add('warning', 'No es posible acceder al archivo <b><i>"' . $archivoNombre . '"</i></b>. Verifique que este exista y tenga los permisos necesarios.');
                return FALSE;
            } else {
                // Inicializando el documento antes de modificarse
                $DOMAnterior = new \DOMDocument('1.0', 'utf-8');
                $DOMAnterior->encoding = 'utf-8';
                $DOMAnterior->preserveWhiteSpace = false;
                $DOMAnterior->formatOutput = true;
                $DOMAnterior->loadXML($contenido);

                $xpathfAnterior = new \DOMXPath($DOMAnterior);

                if ($xpathfAnterior->query($propiedad->getRutaPropiedad())->item(0)->nodeValue != $propiedad->getValor()) {
                    $valorAnteriorPropiedad = $xpathfAnterior->query($propiedad->getRutaPropiedad())->item(0)->nodeValue;
                    $xpathfAnterior->query($propiedad->getRutaPropiedad())->item(0)->nodeValue = $propiedad->getValor();

                    //Guardando datos de la modificacion  
                    $modificacion = new modificacion(new \DateTime(), $propiedad->getPropiedad(), $propiedad->getRutaPropiedad(), $valorAnteriorPropiedad, $propiedad->getValor(), 'MODIFICAR', 'TRUE', 'Aplicando valor de propiedad de perfil. Valor anterior: ' . $valorAnteriorPropiedad . '. Valor actual: ' . $propiedad->getValor());

                    $dm->persist($modificacion);
                    $archivo->addModificacione($modificacion);

                    // Salvando datos en el sistema y en el archivo real
                    $DOMAnterior->save($rutaFile);
                    // Decodificando caracteres
                    $newContent = html_entity_decode(file_get_contents($rutaFile), null, "UTF-8");
                    // Guardando contenido decodificado
                    $file = fopen($rutaFile, "w");
                    fwrite($file, $newContent);
                    fclose($file);
                    // Actualizando contenido decodificado en el sistema
                    $archivo->setContenido($newContent);
                }

                $dm->persist($archivo);
                $dm->flush();
                return TRUE;
            }
        }
    }

    /**
     * Actualiza los Datos de una Propiedad de Perfil
     *
     * @param Request $request Datos de la solicitud
     * @param string $perfil   ID del Perfil
     * @param string $id       ID de la Propiedad de Perfil
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction(Request $request, $perfil, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:propiedadPerfil')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No existe la propiedad de perfil solicitada.');
        }

        $deleteForm = $this->createDeleteForm($perfil, $id);
        $editForm = $this->createForm(new propiedadPerfilType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $dm->persist($document);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('success', 'La propiedad se ha actualizado correctamente.');
            return $this->redirect($this->generateUrl('propiedadperfil_show', array('perfil' => $perfil, 'id' => $id)));
        }

        $this->get('session')->getFlashBag()->add('warning', 'La propiedad no se ha podido actualizar correctamente.');

        return $this->render('ConfigOrionBundle:propiedadPerfil:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'id_perfil' => $perfil,
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Elimina una Propiedad de Perfil
     *
     * @param Request $request Datos de la solicitud
     * @param string $perfil   ID del Perfil
     * @param string $id       ID de la Propiedad de Perfil
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction(Request $request, $perfil, $id) {
        $form = $this->createDeleteForm($perfil, $id);
        $form->bind($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();

            $document = $dm->getRepository('ConfigOrionBundle:propiedadPerfil')->find($id);
            $perfiles = $dm->getRepository('ConfigOrionBundle:perfil')->find($perfil);
            $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->getArchivoByPropiedadPerfil($document);
            if (!$perfiles) {
                throw $this->createNotFoundException('La propiedad que desea eliminar no pertenece al perfil seleccionado');
            }

            if (!$document) {
                throw $this->createNotFoundException('No existe la propiedad de perfil solicitada.');
            }

            $this->get('session')->getFlashBag()->add('success', 'La propiedad se ha eliminado correctamente.');

            $perfiles->removePropiedadesPerfile($document);
            $archivo->removePropiedadesPerfile($document);

            $dm->remove($document);
            $dm->flush();
        } else
            $this->get('session')->getFlashBag()->add('warning', 'La propiedad no se ha podido eliminar correctamente.');

        return $this->redirect($this->generateUrl('perfil_admin', array('id' => $perfil)));
    }

    /**
     * Crea el formulario de eliminar Propiedad de Perfil
     * 
     * @param String $id ID del Archivo    
     */
    private function createDeleteForm($perfil, $id) {
        return $this->createFormBuilder(array('id' => $id, '$perfil' => $perfil))
                        ->add('id', 'hidden')
                        ->add('perfil', 'hidden')
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
