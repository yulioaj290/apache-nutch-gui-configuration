<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\modificacion;
use ConfigOrion\ConfigOrionBundle\Document\perfil;
use ConfigOrion\ConfigOrionBundle\Form\perfilType;
use ConfigOrion\ConfigOrionBundle\Form\perfilAddType;
use ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil;

/**
 * Clase controladora que se encarga de la gestión de Perfiles de Configuración
 */
class perfilController extends Controller {

    /**
     * Muestra la lista de Perfiles de Configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $perfiles = $dm->getRepository('ConfigOrionBundle:perfil')->findAll();

        return $this->render('ConfigOrionBundle:perfil:index.html.twig', array('perfiles' => $perfiles));
    }

    /**
     * Muestra la vista de Crear Perfil de Configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction() {
        $document = new perfil();
        $form = $this->createForm(new perfilType(), $document);

        return $this->render('ConfigOrionBundle:perfil:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Crea un nuevo Perfil de Configuración
     *
     * @param Request $request Datos de la solicitud
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request) {
        $document = new perfil();
        $form = $this->createForm(new perfilType(), $document);
        $form->bind($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El perfil se ha creado correctamente.');
            return $this->redirect($this->generateUrl('perfil_show', array('id' => $document->getId())));
        }

        return $this->render('ConfigOrionBundle:perfil:new.html.twig', array(
                    'document' => $document,
                    'form' => $form->createView()
        ));
    }

    /**
     * Aplica un Perfil de Configuración al Sistema
     * 
     * @param String $id ID del Perfil de Configuración     
     */
    public function aplicarAction($id) {
        $dm = $this->getDocumentManager();
        $perfil = $dm->getRepository('ConfigOrionBundle:perfil')->find($id);

        if (!$perfil) {
            throw $this->createNotFoundException("No existe el perfil solicitado en la base de datos.");
        }

        $ERROR = FALSE;

        $propiedadesPefil = $perfil->getPropiedadesPerfiles();
        foreach ($propiedadesPefil as $propiedad) {
            $ERROR = $this->aplicarPropiedad($propiedad);
            if (!$ERROR)
                return $this->redirect($this->generateUrl('perfil'));
        }

        if ($propiedadesPefil->isEmpty())
            $this->get('session')->getFlashBag()->add('warning', 'El perfil seleccionado no contiene propiedades de configuración.');
        else
            $this->get('session')->getFlashBag()->add('success', 'El perfil se ha aplicado correctamente.');

        return $this->redirect($this->generateUrl('perfil'));
    }

    /**
     * Aplica una propiedad de un Perfil de Configuración
     * 
     * @param propiedadPerfil $propiedad Propiedad a aplicar del Perfil de Configuración
     * @return boolean
     */
    private function aplicarPropiedad($propiedad) {
        $dm = $this->getDocumentManager();
        $archivo = $propiedad->getArchivoId();
        $contenido = $archivo->getContenido();

        // Obteniendo ruta del archivo
        $extension = '';
        $rutaFile = $archivo->getRuta() . '/' . $archivo->getNombre();
        if (\strtolower($archivo->getFormato()) != 'null') {
            $extension = '.' . strtolower($archivo->getFormato());
            $rutaFile .= $extension;
        }

        // Verificando que el archivo existe
        if (!is_writable($rutaFile)) {
            $this->get('session')->getFlashBag()->add('warning', 'No es posible aplicar el perfil seleccionado. Verifique que existan los archivos correspondientes y tengan los permisos necesarios.');
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

    /**
     * Muestra la vista de Ver Datos de un Perfil de Configuración
     *
     * @param String $id ID del Perfil de Configuración  
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:perfil')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No existe el perfil solicitado.');
        }

        $deleteForm = $this->createDeleteForm($id);


        return $this->render('ConfigOrionBundle:perfil:show.html.twig', array(
                    'document' => $document,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Datos de un Perfil de Configuración
     *
     * @param String $id ID del Perfil de Configuración  
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:perfil')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El perfil relacionado no existe en la base de datos.');
        }

        $editForm = $this->createForm(new perfilType(), $document);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:perfil:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los datos de un Perfil de Configuración  
     *
     * @param Request $request Datos de la solicitud
     * @param String $id ID del Perfil de Configuración  
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction(Request $request, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:perfil')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El perfil relacionado no existe en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new perfilType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'El perfil se ha editado correctamente.');
            return $this->redirect($this->generateUrl('perfil_show', array('id' => $id)));
        }

        return $this->render('ConfigOrionBundle:perfil:edit.html.twig', array(
                    'document' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Elimina los Datos de un Perfil de Configuración  
     *
     * @param Request $request Datos de la solicitud
     * @param String $id ID del Perfil de Configuración  
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
            $document = $dm->getRepository('ConfigOrionBundle:perfil')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('El perfil relacionado no existe en la base de datos.');
            }

            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'El perfil se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl('perfil'));
    }

    /**
     * Adiciona una propiedad a un Perfil de Configuración  
     * 
     * @param Request $request Datos de la solicitud
     * @param String $id_archivo ID del archivo
     */
    public function addAction(Request $request, $id_archivo) {
        $form = $this->createForm(new perfilAddType());
        $form->bind($request);
        if ($form->isValid()) {
            $id_perfil = $form->get('id_perfil')->getViewData();
            $id_modificacion = $form->get('id_modificacion')->getData();

            // Obteniendo la referencia del archivo y el perfil
            $dm = $this->getDocumentManager();
            $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);
            $perfil = $dm->getRepository('ConfigOrionBundle:perfil')->find($id_perfil);

            if (!$archivo) {
                throw $this->createNotFoundException('No existe el archivo al cual pertenece la propiedad seleccionada.');
            }

            if (!$perfil) {
                throw $this->createNotFoundException('No existe el perfil seleccionado.');
            }

            // Comprobando que la modificación pertenece al archivo seleccionado
            $encontrado = FALSE;
            $modificaciones = $archivo->getModificaciones();
            $size = count($modificaciones);
            for ($i = 0; $i < $size && !$encontrado; $i++) {
                if ($modificaciones[$i]->getId() == $id_modificacion) {
                    $encontrado = TRUE;
                    $modificacion = $modificaciones[$i];
                }
            }

            if ($encontrado) {
                $propiedad = $modificacion->getPropiedad();
                $rutaPropiedad = $modificacion->getRutaPropiedad();
                $valor = $modificacion->getValorActual();

                // Creando un nuevo documento de tipo propiedadPerfil
                $propiedadPerfil = new propiedadPerfil();
                $propiedadPerfil->setPropiedad($propiedad);
                $propiedadPerfil->setRutaPropiedad($rutaPropiedad);
                $propiedadPerfil->setValor($valor);
                $propiedadPerfil->setArchivoId($archivo);

                // Registrando la nueva propiedadPerfil
                $perfil->addPropiedadesPerfile($propiedadPerfil);
                $archivo->addPropiedadesPerfile($propiedadPerfil);

                $dm->persist($archivo);
                $dm->persist($propiedadPerfil);
                $dm->persist($perfil);
                $dm->flush();

                $this->get('session')->getFlashBag()->add('success', 'Se ha adicionado correctamente la propiedad.');
            }
        }

        if (!$form->isValid() || !$encontrado) {
            $this->get('session')->getFlashBag()->add('danger', 'No se ha podido adicionar correctamente la propiedad.');
        }

        return $this->redirect($this->generateUrl('archivo_admin', array('id' => $id_archivo)));
    }

    /**
     * Muestra la vista de Administrar un Perfil de Configuración  
     * 
     * @param String $id ID del Perfil de Configuración     
     */
    public function adminAction($id) {
        $dm = $this->getDocumentManager();
        $perfil = $dm->getRepository('ConfigOrionBundle:perfil')->find($id);
        if (!$perfil) {
            throw $this->createNotFoundException('El perfil seleccionado no ha sido registrado en el sistema.');
        } else {
            $propiedades = $perfil->getPropiedadesPerfiles();
            $instancia = array();

            foreach ($propiedades as $propiedad) {
                $id_propiedad = $propiedad->getId();
                $archivo = $propiedad->getArchivoId();
                $instancia[$id_propiedad] = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($archivo);
            }

            return $this->render('ConfigOrionBundle:perfil:admin.html.twig', array(
                        'perfil' => $perfil,
                        'propiedades' => $propiedades,
                        'instancia' => $instancia,
            ));
        }
    }

    /**
     * Crea el formulario de Eliminar un Perfil de Configuración  
     * 
     * @param String $id ID del Perfil de Configuración  
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
