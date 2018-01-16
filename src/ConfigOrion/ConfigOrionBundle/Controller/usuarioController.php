<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\usuario;
use ConfigOrion\ConfigOrionBundle\Form\usuarioType;
use ConfigOrion\ConfigOrionBundle\Form\updatePasswordType;
use ConfigOrion\ConfigOrionBundle\Form\updatePerfilType;
use ConfigOrion\ConfigOrionBundle\Form\loginType;

/**
 * Clase controladora que se encarga de la gestión de Usuarios
 */
class usuarioController extends Controller {

    /**
     * Muestra la lista de Usuarios registrados en el sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $documents = $dm->getRepository('ConfigOrionBundle:usuario')->findAll();

        return $this->render('ConfigOrionBundle:usuario:index.html.twig', array('documents' => $documents));
    }

    /**
     * Muestra la vista de Crear Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $document = new usuario(null, null, null, null, null, null, null);
        $form = $this->createForm(new usuarioType(), $document);

        return $this->render('ConfigOrionBundle:usuario:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Crea un nuevo Usuario
     *
     * @param Request $request Datos de la solicitud
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $document = new usuario(null, null, null, null, null, null, null);
        $form = $this->createForm(new usuarioType(), $document);
        $form->bind($request);

        $dm = $this->getDocumentManager();

        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($document);
        $password = $encoder->encodePassword($document->getPassword(), $document->getSalt());
        if (!preg_match('/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/', $document->getPassword())) {
            $this->get('session')->getFlashBag()->add('warning', 'La contraseña debe cumplir con los siguientes parámetros:'
                    . '<ul>'
                    . '<li>Contener al menos una letra mayúscula.</li>'
                    . '<li>Contener al menos una letra minúscula.</li>'
                    . '<li>Contener al menos un número o caracter especial.</li>'
                    . '<li>Debe poseer 8 caracteres como mínimo.</li>'
                    . '</ul>');
        } elseif ($form->isValid()) {
            $document->setPassword($password);

            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El usuario se ha creado correctamente.');
            return $this->redirect($this->generateUrl('usuario_show', array('id' => $document->getId())));
        }

        return $this->render('ConfigOrionBundle:usuario:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Mostra la vista de Ver Datos de Usuario
     *
     * @param string $id ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El usuario relacionado no se encontró en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);


        return $this->render('ConfigOrionBundle:usuario:show.html.twig', array(
                    'document' => $document,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Datos de Usuario
     *
     * @param string $id ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El usuario relacionado no se encontró en la base de datos.');
        }

        $editForm = $this->createForm(new usuarioType(), $document);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:usuario:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los datos de un Usuario
     *
     * @param Request $request Datos de la solicitud
     * @param string $id       ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction(Request $request, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);
        $lastPassword = $document->getPassword();
        $lastSalt = $document->getSalt();

        if (!$document) {
            throw $this->createNotFoundException('No se encontró el usuario relacionado en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new usuarioType(), $document);
        $editForm->bind($request);

        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($document);
        $password = $encoder->encodePassword($document->getPassword(), $document->getSalt());

        if (!preg_match('/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/', $document->getPassword()) && $document->getPassword() != "") {
            $this->get('session')->getFlashBag()->add('warning', 'La contraseña debe cumplir con los siguientes parámetros:'
                    . '<ul>'
                    . '<li>Contener al menos una letra mayúscula.</li>'
                    . '<li>Contener al menos una letra minúscula.</li>'
                    . '<li>Contener al menos un número o caracter especial.</li>'
                    . '<li>Debe poseer 8 caracteres como mínimo.</li>'
                    . '</ul>');
        } elseif ($editForm->isValid()) {
            if ($editForm->get('password')->getData() == "") {
                $password = $lastPassword;
            }
            $document->setPassword($password);
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El usuario se ha editado correctamente.');
            return $this->redirect($this->generateUrl('usuario_show', array('id' => $id)));
        }

        return $this->render('ConfigOrionBundle:usuario:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Elimina un Usuario registrado en el sistema
     *
     * @param Request $request Datos de la solicitud
     * @param string $id       ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('No se encontró el usuario relacionado en la base de datos.');
            }

            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'El usuario se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl('usuario'));
    }

    /**
     * Muestra la vista de Cambiar Contraseña
     *
     * @param string $id ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function changePasswordAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El usuario relacionado no se encontró en la base de datos.');
        } elseif ($document->getUsername() == $this->getUser()->getUsername()) {

            $editForm = $this->createForm(new updatePasswordType(), $document);

            return $this->render('ConfigOrionBundle:usuario:changePassword.html.twig', array(
                        'document' => $document,
                        'edit_form' => $editForm->createView(),
            ));
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'Ud. no tiene acceso a este recurso.');
            return $this->redirect($this->generateUrl('instancia'));
        }
    }

    /**
     * Actualiza la contraseña de un Usuario
     *
     * @param Request $request Datos de la solicitud
     * @param string $id       ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updatePasswordAction(Request $request, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

        $lastPassword = $document->getPassword();

        if (!$document) {
            throw $this->createNotFoundException('No se encontró el usuario relacionado en la base de datos.');
        } elseif ($document->getUsername() == $this->getUser()->getUsername()) {
            $editForm = $this->createForm(new updatePasswordType(), $document);
            $editForm->bind($request);

            $factory = $this->get('security.encoder_factory');
            $encoder = $factory->getEncoder($document);
            $password = $encoder->encodePassword($editForm->get('password')->getData(), $this->getUser()->getSalt());
            $passwordActual = $encoder->encodePassword($editForm->get('passwordActual')->getData(), $this->getUser()->getSalt());

            if ($passwordActual != $lastPassword) {
                $this->get('session')->getFlashBag()->add('danger', 'La contraseña actual proporcionada no se corresponde.');
            } elseif (!preg_match('/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/', $document->getPassword())) {
                $this->get('session')->getFlashBag()->add('warning', 'La contraseña debe cumplir con los siguientes parámetros:'
                        . '<ul>'
                        . '<li>Contener al menos una letra mayúscula.</li>'
                        . '<li>Contener al menos una letra minúscula.</li>'
                        . '<li>Contener al menos un número o caracter especial.</li>'
                        . '<li>Debe poseer 8 caracteres como mínimo.</li>'
                        . '</ul>');
            } elseif ($editForm->isValid()) {
                $document->setPassword($password);
                $dm->persist($document);
                $dm->flush();

                // Mostrando mensaje
                $this->get('session')->getFlashBag()->add('success', 'La contraseña se ha cambiado correctamente.');
                return $this->redirect($this->generateUrl('usuario_perfil', array('id' => $id)));
            }


            return $this->render('ConfigOrionBundle:usuario:changePassword.html.twig', array(
                        'document' => $document,
                        'edit_form' => $editForm->createView(),
            ));
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'Ud. no tiene acceso a este recurso.');
            return $this->redirect($this->generateUrl('instancia'));
        }
    }

    /**
     * Muestra la vista con los Datos del Perfil de un Usuario
     *
     * @param string $id ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function perfilAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El usuario relacionado no se encontró en la base de datos.');
        }

        return $this->render('ConfigOrionBundle:usuario:perfil.html.twig', array(
                    'document' => $document,));
    }

    /**
     * Muestra la vista de Editar Datos de perfil Usuario
     *
     * @param string $id ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function changePerfilAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El usuario relacionado no se encontró en la base de datos.');
        } elseif ($document->getUsername() == $this->getUser()->getUsername()) {

            $editForm = $this->createForm(new updatePerfilType(), $document);

            return $this->render('ConfigOrionBundle:usuario:changePerfil.html.twig', array(
                        'document' => $document,
                        'edit_form' => $editForm->createView(),
            ));
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'Ud. no tiene acceso a este recurso.');
            return $this->redirect($this->generateUrl('instancia'));
        }
    }

    /**
     * Actualiza los datos de perfil de un Usuario
     *
     * @param Request $request Datos de la solicitud
     * @param string $id       ID del Usuario
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updatePerfilAction(Request $request, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:usuario')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No se encontró el usuario relacionado en la base de datos.');
        } elseif ($document->getUsername() == $this->getUser()->getUsername()) {

            $editForm = $this->createForm(new updatePerfilType(), $document);
            $editForm->bind($request);

            if ($editForm->isValid()) {
                $dm->persist($document);
                $dm->flush();

                // Mostrando mensaje
                $this->get('session')->getFlashBag()->add('success', 'Los datos de su perfil se han editado correctamente.');
                return $this->redirect($this->generateUrl('usuario_perfil', array('id' => $id)));
            }

            return $this->render('ConfigOrionBundle:usuario:changePerfil.html.twig', array(
                        'document' => $document,
                        'edit_form' => $editForm->createView(),
            ));
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'Ud. no tiene acceso a este recurso.');
            return $this->redirect($this->generateUrl('instancia'));
        }
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

    public function loginAction() {
        if ($this->get('security.context')->isGranted('ROLE_USER') === false) {
            $peticion = $this->getRequest();
            $sesion = $peticion->getSession();
            $error = $peticion->attributes->get(
                    SecurityContext::AUTHENTICATION_ERROR, $sesion->get(SecurityContext::AUTHENTICATION_ERROR)
            );


            $loginForm = $this->createForm(new loginType());

            return $this->render('ConfigOrionBundle:Default:login.html.twig', array(
                        'login_form' => $loginForm->createView(),
                        'last_username' => $sesion->get(SecurityContext::LAST_USERNAME),
                        'error'
                        => $error
            ));
        } else {
            return $this->redirect($this->generateUrl('instancia'));
        }
    }

}
