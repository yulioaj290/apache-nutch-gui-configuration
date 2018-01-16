<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\instancia;
use ConfigOrion\ConfigOrionBundle\Form\instanciaType;
use ConfigOrion\ConfigOrionBundle\Form\archivarModificacionType;

/**
 * Clase controladora que se encarga de la gestión de Instancias de Nutch
 */
class instanciaController extends Controller {

    /**
     * Muestra la lista de instancias registradas en el sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();
        $formArchivar = $this->createForm(new archivarModificacionType());

        $documents = $dm->getRepository('ConfigOrionBundle:instancia')->findAll();

        return $this->render('ConfigOrionBundle:instancia:index.html.twig', array(
                    'documents' => $documents,
                    'formArchivar' => $formArchivar->createView(),
        ));
    }

    /**
     * Muestra la vista de Crear Instancia
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $dm = $this->getDocumentManager();
        $document = new instancia();

        // Obteniendo el directorio de despliegue de las instancias de Nutch
        $directorioInstancias = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();
        $document->setRuta(substr($directorioInstancias, 0, strlen($directorioInstancias) - 1));
        $form = $this->createForm(new instanciaType(), $document);

        return $this->render('ConfigOrionBundle:instancia:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Crea una nueva instancia de Nutch
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $document = new instancia();
        $form = $this->createForm(new instanciaType(), $document);
        $form->bind($request);

        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->findOneBy(array('nombre' => $form->get('nombre')->getData()));
        $rutaInstancia = $dm->getRepository('ConfigOrionBundle:instancia')->findOneBy(array('ruta' => $form->get('ruta')->getData()));

        // Obteniendo el directorio de despliegue de las instancias de Nutch
        $directorioInstancias = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();

        if (strncasecmp($directorioInstancias, $form->get('ruta')->getData(), strlen($directorioInstancias)) != 0) { // Verificando que la ruta este dentro del directorio de despliegue de instancias
            $this->get('session')->getFlashBag()->add('danger', 'Ud. no tiene acceso a este directorio. Verifique la ruta entrada.');
        } elseif ($instancia != '') { // Verficando duplicidad de nombre de instancia
            $this->get('session')->getFlashBag()->add('warning', 'Ya existe una instancia con el nombre <b>"' . $form->get('nombre')->getData() . '"</b>. Se recomienda escoger otro nombre.');
        } elseif ($rutaInstancia != '') {// Verificando que no se use una ruta de instancia 2 veces
            $this->get('session')->getFlashBag()->add('warning', 'La ruta especificada est&aacute; siendo usada por la instancia <b>"' . $rutaInstancia->getNombre() . '"</b>. Se recomienda escoger otra ruta.');
        } elseif (substr($form->get('ruta')->getData(), strlen($form->get('ruta')->getData()) - 1, 1) == '/') {  // Verificando que la ruta no termine en /
            $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede terminar con el caracter "/".');
        } elseif (preg_match('/\/\//', $form->get('ruta')->getData()) == 1) {  // Verificando que la ruta no posea dos /
            $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede contener dos caracteres "/" seguidos.');
        } elseif (!is_dir($form->get('ruta')->getData())) {
            // Verificando que la ruta existe
            $this->get('session')->getFlashBag()->add('danger', 'La ruta especificada no existe.');
        } elseif (!preg_match('/^[\w\d\s_áéíóúñ]{4,30}$/', $form->get('nombre')->getData())) {
            $this->get('session')->getFlashBag()->add('danger', 'El nombre de la instancia debe ser de 4 a 30 caracteres y no puede contener caracteres extraños.');
        } elseif ($form->isValid()) {
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'La instancia se ha creado correctamente.');
            return $this->redirect($this->generateUrl('instancia_show', array('id' => $document->getId())));
        }

        return $this->render('ConfigOrionBundle:instancia:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Muestra la vista de Ver Datos de Instancia
     *
     * @param string $id ID de la instancia
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:instancia')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La instancia seleccionada ya no se encuentra disponible en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:instancia:show.html.twig', array(
                    'document' => $document,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Instancia.
     *
     * @param string $id ID de la instancia
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:instancia')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La instancia seleccionada ya no se encuentra disponible en la base de datos.');
        }

        $editForm = $this->createForm(new instanciaType(), $document);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:instancia:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los datos de una Instancia
     *
     * @param Request $request Datos de la solicitud
     * @param string $id       ID de la instancia
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction(Request $request, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:instancia')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La instancia seleccionada ya no se encuentra disponible en la base de datos.');
        }

        $instanciaAnterior = clone $document;

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new instanciaType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->findOneBy(array('nombre' => $editForm->get('nombre')->getData()));
            $rutaInstancia = $dm->getRepository('ConfigOrionBundle:instancia')->findOneBy(array('ruta' => $editForm->get('ruta')->getData()));
            // Obteniendo directorio de despliegue de instancias
            $directorioInstancias = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();

            if (strncasecmp($directorioInstancias, $editForm->get('ruta')->getData(), strlen($directorioInstancias)) != 0) { // Verificando que la ruta este dentro del directorio de despliegue de instancias
                $this->get('session')->getFlashBag()->add('danger', 'Ud. no tiene acceso a este directorio. Verifique la ruta entrada!!!');
            } elseif ($instancia != '' && $instanciaAnterior->getNombre() != $instancia->getNombre()) { // Verficando duplicidad de nombre de instancia
                $this->get('session')->getFlashBag()->add('warning', 'Ya existe una instancia con el nombre <b>"' . $editForm->get('nombre')->getData() . '"</b>. Se recomienda escoger otro nombre.');
            } elseif ($rutaInstancia != '' && $instanciaAnterior->getRuta() != $rutaInstancia->getRuta()) {// Verificando que no se use una ruta de instancia 2 veces
                $this->get('session')->getFlashBag()->add('warning', 'La ruta especificada est&aacute; siendo usada por la instancia <b>"' . $rutaInstancia->getNombre() . '"</b>. Se recomienda escoger otra ruta.');
            } elseif (!is_dir($editForm->get('ruta')->getData())) {// Verificando que la ruta existe
                $this->get('session')->getFlashBag()->add('danger', 'La ruta especificada no existe.');
            } elseif (substr($editForm->get('ruta')->getData(), strlen($editForm->get('ruta')->getData()) - 1, 1) == '/') {  // Verificando que la ruta no termine en /
                $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede terminar con el caracter "/".');
            } elseif (preg_match('/\/\//', $editForm->get('ruta')->getData()) == 1) {  // Verificando que la ruta no posea dos /
                $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede contener dos caracteres "/" seguidos.');
            } elseif (!preg_match('/^[\w\d\s_áéíóúñ]{4,30}$/', $editForm->get('nombre')->getData())) {
            $this->get('session')->getFlashBag()->add('danger', 'El nombre de la instancia debe ser de 4 a 30 caracteres y no puede contener caracteres extraños.');
			} else {
                // Actualizando las rutas de los plugins y archivos si se modifica la ruta actual
                if ($editForm->get('ruta')->getData() != $instanciaAnterior->getRuta()) {
                    $plugins = $document->getPlugins();
                    foreach ($plugins as $plugin) {
                        $archivos = $plugin->getArchivos();
                        foreach ($archivos as $archivo) {
                            $newRuta = str_replace($instanciaAnterior->getRuta(), $editForm->get('ruta')->getData(), $archivo->getRuta());
                            $archivo->setRuta($newRuta);
                            $dm->persist($archivo);
                        }
                        $newRuta = str_replace($instanciaAnterior->getRuta(), $editForm->get('ruta')->getData(), $plugin->getRuta());
                        $plugin->setRuta($newRuta);
                        $dm->persist($plugin);
                    }

                    $archivos = $document->getArchivos();
                    foreach ($archivos as $archivo) {
                        $newRuta = str_replace($instanciaAnterior->getRuta(), $editForm->get('ruta')->getData(), $archivo->getRuta());
                        $archivo->setRuta($newRuta);
                        $dm->persist($archivo);
                    }
                }

                $dm->persist($document);
                $dm->flush();

                // Mostrando mensaje
                $this->get('session')->getFlashBag()->add('success', 'La instancia se ha editado correctamente.');

                return $this->redirect($this->generateUrl('instancia_show', array('id' => $id)));
            }
        }
        return $this->render('ConfigOrionBundle:instancia:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Elimina los datos de una Instancia.
     *
     * @param Request $request Datos de la solicitud
     * @param string $id       ID de la instancia
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            // Inicializando el Administrador de Documentos
            $dm = $this->getDocumentManager();
            $document = $dm->getRepository('ConfigOrionBundle:instancia')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('La instancia seleccionada ya no se encuentra disponible en la base de datos.');
            }

            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'La instancia se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl('instancia'));
    }

    /**
     * Muestra la vista de Administrar Instancia
     *
     * @param string $id ID de la instancia
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function adminAction($id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:instancia')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('La instancia seleccionada ya no se encuentra disponible en la base de datos.');
        }

        $plugins = $document->getPlugins();
        $archivos = $document->getArchivos();

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:instancia:admin.html.twig', array(
                    'document' => $document,
                    'plugins' => $plugins,
                    'archivos' => $archivos,
                    'delete_form' => $deleteForm->createView(),));
    }

    
    /**
     * Crea el formulario de Eliminar Instancia
     * 
     * @param String $id ID de la instancia
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
