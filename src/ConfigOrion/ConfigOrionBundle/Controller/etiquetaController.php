<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\etiqueta;
use ConfigOrion\ConfigOrionBundle\Form\etiquetaType;

/**
 * Clase controladora que se encarga de la gestión de Etiquetas.
 */
class etiquetaController extends Controller {

    /**
     * Muestra la lista de etiquetas registradas en el sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $documents = $dm->getRepository('ConfigOrionBundle:etiqueta')->findAll();

        return $this->render('ConfigOrionBundle:etiqueta:index.html.twig', array('documents' => $documents));
    }

    /**
     * Muestra la vista de Crear Etiqueta
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $document = new etiqueta();
        $form = $this->createForm(new etiquetaType(), $document);

        return $this->render('ConfigOrionBundle:etiqueta:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Crea una nueva Etiqueta
     *
     * @param Request $request Datos de la solicitud
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $document = new etiqueta();
        $form = $this->createForm(new etiquetaType(), $document);
        $form->bind($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $dm->persist($document);
            $dm->flush();

            return $this->redirect($this->generateUrl('etiqueta_show', array('id' => $document->getId())));
        }

        return $this->render('ConfigOrionBundle:etiqueta:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Muestra los datos de una Etiqueta
     * 
     * @param String $id_archivo ID del archivo
     * @param String $id ID de la etiqueta
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id_archivo, $id) {
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        $document = $dm->getRepository('ConfigOrionBundle:etiqueta')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La etiqueta seleccionada no se encuentra registrada en la base de datos');
        }

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($archivo);

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:etiqueta:show.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'instancia_item' => $instancia,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Datos de una Etiqueta
     *
     * @param String $id_archivo ID del archivo
     * @param String $id ID de la etiqueta
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id_archivo, $id) {
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        $document = $dm->getRepository('ConfigOrionBundle:etiqueta')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La etiqueta seleccionada no se encuentra registrada en la base de datos');
        }

        $editForm = $this->createForm(new etiquetaType(), $document);
        $deleteForm = $this->createDeleteForm($id);

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($archivo);

        return $this->render('ConfigOrionBundle:etiqueta:edit.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'instancia_item' => $instancia,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los datos de una Etiqueta
     *
     * @param String $id_archivo ID del archivo
     * @param String $id ID de la etiqueta
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction($id_archivo, Request $request, $id) {
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        $document = $dm->getRepository('ConfigOrionBundle:etiqueta')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La etiqueta seleccionada no se encuentra registrada en la base de datos');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new etiquetaType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'La etiqueta se ha editado correctamente.');
            return $this->redirect($this->generateUrl('etiqueta_show', array('id' => $id, 'id_archivo' => $id_archivo)));
        }

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($archivo);

        return $this->render('ConfigOrionBundle:etiqueta:edit.html.twig', array(
                    'document' => $document,
                    'archivo' => $archivo,
                    'instancia_item' => $instancia,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));       
    }

    /**
     * Elimina los datos de una Etiqueta
     *
     * @param String $id_archivo ID del archivo
     * @param String $id ID de la etiqueta
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction($id_archivo, Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        if ($form->isValid()) {
            $document = $dm->getRepository('ConfigOrionBundle:etiqueta')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('La etiqueta seleccionada no se encuentra registrada en la base de datos');
            }

            $archivo->removeEtiqueta($document);
            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'La etiqueta se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl('archivo_admin', array('id' => $archivo->getId())));
    }

    /**
     * Crea el formulario de eliminar Etiqueta
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

}
