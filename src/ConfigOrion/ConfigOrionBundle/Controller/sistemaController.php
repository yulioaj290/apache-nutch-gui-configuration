<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\sistema;
use ConfigOrion\ConfigOrionBundle\Form\sistemaType;

/**
 * Clase controladora que se encarga de la gestión de la Configuración del Sistema
 */
class sistemaController extends Controller {

    /**
     * Muestra la lista de las Propiedades de Configuración del Sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $documents = $dm->getRepository('ConfigOrionBundle:sistema')->findAll();

        return $this->render('ConfigOrionBundle:sistema:index.html.twig', array('documents' => $documents));
    }

    /**
     * BORRAR METODO PORQUE NO SE PERMITE CREAR NUEVAS PROPIEDADES
     */
    public function newAction() {
        $document = new sistema();
        $form = $this->createForm(new sistemaType(), $document);

        return $this->render('ConfigOrionBundle:sistema:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * BORRAR METODO PORQUE NO SE PERMITE CREAR NUEVAS PROPIEDADES
     */
    public function createAction(Request $request) {
        $document = new sistema();
        $form = $this->createForm(new sistemaType(), $document);
        $form->bind($request);

        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        // Verficando duplicidad de nombre de instancia
        $sistema = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => $form->get('nombre')->getData()));
        if ($sistema != '') {
            $this->get('session')->getFlashBag()->add('warning', 'Ya existe una propiedad de sistema con el nombre <b>"' . $form->get('nombre')->getData() . '"</b>. Se recomienda escoger otro nombre.');
        } elseif ($form->isValid()) {
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'La propiedad de sistema se ha creado correctamente.');
            return $this->redirect($this->generateUrl('sistema_show', array('id' => $document->getId())));
        }

        return $this->render('ConfigOrionBundle:sistema:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Muestra los Datos de una Propiedad de Configuración del Sistema
     *
     * @param String $id ID de la propiedad del sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:sistema')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La propiedad de sistema relacionada no se encontró en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);


        return $this->render('ConfigOrionBundle:sistema:show.html.twig', array(
                    'document' => $document,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Propiedad de Configuración del Sistema
     *
     * @param string $id ID de la propiedad del sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:sistema')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La propiedad de sistema relacionada no se encontró en la base de datos.');
        }

        $editForm = $this->createForm(new sistemaType(), $document);
        //$deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:sistema:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    //'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los datos de una Propiedad de Configuración del Sistema
     *
     * @param Request $request Datos de la solicitud
     * @param string $id       ID de la propiedad del sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction(Request $request, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:sistema')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La propiedad de sistema relacionada no se encontró en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new sistemaType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'La propiedad de sistema se ha editado correctamente.');
            return $this->redirect($this->generateUrl('sistema_show', array('id' => $id)));
        }

        return $this->render('ConfigOrionBundle:sistema:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

     /**
     * BORRAR METODO PORQUE NO SE PERMITE ELIMINAR PROPIEDADES
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            // Inicializando el Administrador de Documentos
            $dm = $this->getDocumentManager();
            $document = $dm->getRepository('ConfigOrionBundle:sistema')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('La propiedad de sistema relacionada no se encontró en la base de datos.');
            }

            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'La propiedad de sistema se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl('sistema'));
    }

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
