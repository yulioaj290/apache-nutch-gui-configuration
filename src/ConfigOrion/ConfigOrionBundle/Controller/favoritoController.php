<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\favorito;
use ConfigOrion\ConfigOrionBundle\Form\favoritoType;
use ConfigOrion\ConfigOrionBundle\Form\uploadType;
use ConfigOrion\ConfigOrionBundle\Form\favoritoSetType;
use ConfigOrion\ConfigOrionBundle\ExternalClases\FavoritoXML;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Clase controladora que se encarga de la gestión de Favoritos
 */
class favoritoController extends Controller {

    /**
     * Muestra la lista de archivos favoritos registrados en el sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $favoritos = $dm->getRepository('ConfigOrionBundle:favorito')->findAll();
        $form = $this->createForm(new favoritoSetType());

        $form_upload = $this->createForm(new uploadType());

        return $this->render('ConfigOrionBundle:favorito:admin.html.twig', array('favoritos' => $favoritos,
                    'instanciaForm' => $form->createView(),
                    'form_upload_file' => $form_upload->createView()));
    }

    /**
     * Muesta la vista de Crear Favorito
     *
     * @param String $id_archivo ID del archivo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction($id_archivo) {
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        if (!$archivo) {
            throw $this->createNotFoundException("No es posible crear un favorito a partir de un archivo que no existe.");
        }

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($archivo);

        if (!$instancia) {
            throw $this->createNotFoundException("No es posible crear un favorito a partir de una instancia que no existe");
        }

        $favoritoNombre = $this->getNombreFavorito($archivo);

        if (!$favoritoNombre) {
            $this->get('session')->getFlashBag()->add('warning', 'El archivo <i><b>"' . $archivo->getNombre() . '"</b></i> ya se encuentra registrado como favorito.');
            return $this->redirect($this->generateUrl('instancia_admin', array('id' => $instancia->getId())));
        }

        $favorito = new favorito();
        $favorito->setNombreArchivo($favoritoNombre);
        $favorito->setNombreInstancia($instancia->getNombre());
        $pos = strlen($instancia->getRuta()) + 1;
        $ruta = substr($archivo->getRuta(), $pos);
        $favorito->setRutaArchivo($ruta);
        $form = $this->createForm(new favoritoType(), $favorito);

        return $this->render('ConfigOrionBundle:favorito:new.html.twig', array(
                    'favorito' => $favorito,
                    'form' => $form->createView(),
                    'archivo' => $id_archivo,
        ));
    }

    /**
     * Crea un nuevo Favorito
     *
     * @param Request $request Datos de la solicitud
     * @param String $id_archivo ID del archivo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction($id_archivo, Request $request) {
        $dm = $this->getDocumentManager();
        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->find($id_archivo);

        if (!$archivo) {
            throw $this->createNotFoundException("No es posible crear un favorito a partir de un archivo que no existe.");
        }

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($archivo);

        if (!$instancia) {
            throw $this->createNotFoundException("No es posible crear un favorito a partir de una instancia que no existe");
        }

        $favoritoNombre = $this->getNombreFavorito($archivo);

        if (!$favoritoNombre) {
            $this->get('session')->getFlashBag()->add('warning', 'El archivo <i><b>"' . $archivo->getNombre() . '"</b></i> ya se encuentra registrado como favorito.');
            return $this->redirect($this->generateUrl('instancia_admin', array('id' => $instancia->getId())));
        }

        $favorito = new favorito();
        $favorito->setNombreArchivo($favoritoNombre);
        $favorito->setNombreInstancia($instancia->getNombre());
        $pos = strlen($instancia->getRuta()) + 1;
        $ruta = substr($archivo->getRuta(), $pos);
        $favorito->setRutaArchivo($ruta);
        $form = $this->createForm(new favoritoType(), $favorito);
        $form->bind($request);

        if ($form->isValid()) {
            $favoritoDescripcion = $form->get('descripcion')->getData();
            $favorito->setContenido($archivo->getContenido());
            $fecha = date('d/m/Y');
            $favorito->setDescripcion($favoritoDescripcion . ". Creado el $fecha");
            $favorito->setFormato($archivo->getFormato());
            $dm->persist($favorito);
            $dm->flush();
            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'La configuración favorita se ha creado correctamente.');
            return $this->redirect($this->generateUrl('instancia_admin', array('id' => $instancia->getId())));
        }

        return $this->render('ConfigOrionBundle:favorito:new.html.twig', array(
                    'favorito' => $favorito,
                    'form' => $form->createView(),
                    'archivo' => $id_archivo,
        ));
    }

    /**
     * Devuelve el nombre del Archivo Favorito y comprueba que este no haya sido
     * registrado anteriormente.
     * 
     * @param String $id_archivo ID del Archivo 
     * @return String|boolean Retorna el nombre del archivo Ej. <b>favorito-(4)</b>, o <FALSE> en caso
     * de que el archivo haya sido registrado anteriormente   
     */
    private function getNombreFavorito($archivo) {
        $dm = $this->getDocumentManager();
        $archivosFavoritos = $dm->getRepository('ConfigOrionBundle:favorito')->findAll();

        $cantArchivos = 0;
        // Obteniendo el nombre del Archivo
        $archivoNombre = $archivo->getNombre();
        $archivoContenido = hash('md5', $archivo->getContenido());

        // Verificando si el archivo fue registrado anteriormente como Favorito
        foreach ($archivosFavoritos as $favorito) {
            $favoritoNombre = strstr($favorito->getNombreArchivo(), '-(', TRUE);
            if ($favoritoNombre === $archivoNombre) {
                if (strcasecmp(hash('md5', $favorito->getContenido()), $archivoContenido) == 0) {
                    return FALSE;
                }
                $cantArchivos++;
            }
        }

        $cantArchivos++;
        return $archivoNombre . "-($cantArchivos)";
    }

    /**
     * Aplica la configuración de un archivo favorito a una instancia de Nutch
     * 
     * @param Request $request Datos de la solicitud    
     */
    public function aplicarAction($id_favorito, Request $request) {
        $encontrado = FALSE;
        $archivo = NULL;
        $form = $this->createForm(new favoritoSetType());
        $form->bind($request);
        if ($form->isValid()) {
            $id_instancia = $form->get('id_instancia')->getViewData();
            $dm = $this->getDocumentManager();
            $favorito = $dm->getRepository('ConfigOrionBundle:favorito')->find($id_favorito);
            $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia);

            if (!$favorito) {
                throw $this->createNotFoundException('No existe la configuración favorita.');
            }

            if (!$instancia) {
                throw $this->createNotFoundException('No existe la instancia seleccionada.');
            }

            $favoritoNombre = strstr($favorito->getNombreArchivo(), '-(', TRUE);

            $archivosInstancia = $instancia->getArchivos();
            $size = count($archivosInstancia);
            for ($i = 0; $i < $size && !$encontrado; $i++) {
                $nombreArchivo = $archivosInstancia[$i]->getNombre();
                if ($nombreArchivo == $favoritoNombre) {
                    $archivo = $archivosInstancia[$i];
                    $encontrado = TRUE;
                }
            }

            if ($encontrado) {
                // Obteniendo el contenido del favorito
                $contenido = $favorito->getContenido();
                // Obteniendo el formato del archivo
                $formato = strtolower($archivo->getFormato());

                // Obteniendo ruta del archivo
                $extension = '';
                $rutaFile = $archivo->getRuta() . '/' . $archivo->getNombre();
                if (\strtolower($archivo->getFormato()) != 'null') {
                    $extension = '.' . strtolower($archivo->getFormato());
                    $rutaFile .= $extension;
                }

                // Verificando que el archivo existe
                if (!is_writable($rutaFile)) {
                    $this->get('session')->getFlashBag()->add('warning', 'No es posible aplicar la configuración favorita. Verifique que exista el archivo correspondiente y tenga los permisos necesarios.');
                } else {
                    if ($formato == 'xml') {
                        // Inicializando el documento antes de modificarlo
                        $DOMAnterior = new \DOMDocument('1.0', 'utf-8');
                        $DOMAnterior->encoding = 'utf-8';
                        $DOMAnterior->preserveWhiteSpace = false;
                        $DOMAnterior->formatOutput = true;
                        $DOMAnterior->loadXML($contenido);

                        // Eliminando las etiquetas del archivo
                        $etiquetas = $archivo->getEtiquetas();
                        $propiedadesPerfil = $archivo->getPropiedadesPerfiles();

                        foreach ($etiquetas as $tag) {
                            $archivo->removeEtiqueta($tag);
                            $dm->remove($tag);
                        }

                        // Eliminando las propiedades de perfiles
                        foreach ($propiedadesPerfil as $propiedad) {
                            $archivo->removePropiedadesPerfile($propiedad);
                            $perfil = $dm->getRepository('ConfigOrionBundle:perfil')->getPerfilByPropiedadPerfil($propiedad);
                            $perfil->removePropiedadesPerfile($propiedad);
                            $dm->remove($propiedad);
                            $dm->persist($perfil);
                        }

                        // Salvando datos en el sistema y en el archivo real
                        $DOMAnterior->save($rutaFile);
                        // Decodificando caracteres
                        $newContent = html_entity_decode(file_get_contents($rutaFile), null, "UTF-8");
                    } else {
                        $newContent = $contenido;
                    }

                    // Guardando contenido en el archivo
                    $file = fopen($rutaFile, "w");
                    fwrite($file, $newContent);
                    fclose($file);
                    // Actualizando contenido del archivo en el sistema
                    $archivo->setContenido($newContent);

                    $dm->persist($archivo);
                    $dm->flush();
                    $this->get('session')->getFlashBag()->add('success', 'La configuración favorita se ha aplicado correctamente.');
                }
            } else {
                $this->get('session')->getFlashBag()->add('warning', 'La configuración favorita no es compatible con la instancia seleccionada.');
            }
        } else
            $this->get('session')->getFlashBag()->add('danger', 'No es posible aplicar la configuración favorita.');

        return $this->redirect($this->generateUrl('favorito'));
    }

    /**
     * Muestra la vista con los datos de un Favorito
     *
     * @param String $id ID del Favorito
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:favorito')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No existe la configuración favorita.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:favorito:show.html.twig', array(
                    'document' => $document,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de editar datos de un Favorito
     *
     * @param String $id ID del Favorito
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:favorito')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No existe la configuración favorita.');
        }

        $editForm = $this->createForm(new favoritoType(), $document);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ConfigOrionBundle:favorito:edit.html.twig', array(
                    'favorito' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Actualiza los datos de un Favorito
     *
     * @param String $id ID del Favorito
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction(Request $request, $id) {
        $dm = $this->getDocumentManager();

        $document = $dm->getRepository('ConfigOrionBundle:favorito')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No existe la configuración favorita.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new favoritoType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'La configuración favorita se ha modificado correctamente.');
            return $this->redirect($this->generateUrl('favorito_show', array('id' => $id)));
        }

        return $this->render('ConfigOrionBundle:favorito:edit.html.twig', array(
                    'favorito' => $document,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Elimina los datos de un Favorito
     *
     * @param String $id ID del Favorito
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $document = $dm->getRepository('ConfigOrionBundle:favorito')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('No existe la configuración favorita.');
            }

            $dm->remove($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'La configuración favorita se ha eliminado correctamente.');
        }

        return $this->redirect($this->generateUrl('favorito'));
    }

    /**
     * Exporta todas las configuraciones favoritas registradas en el sistema a un archivo ZIP   
     */
    public function exportarAction() {
        // Creando una instancia de la clase FavoritoXML
        $favoritoXML = $this->inicializarFavoritoXML();
        if ($favoritoXML) {
            $dm = $this->getDocumentManager();
            // Obteniendo las configuraciones favoritas
            $favoritos = $dm->getRepository('ConfigOrionBundle:favorito')->findAll();
            // Exportando los favoritos
            $favoritoXML->exportar($favoritos);
            // Enviando el archivo ZIP al navegador            
            $archivoZIP = $favoritoXML->getNombreArchivoZIP();
            $zipFile = file_get_contents($archivoZIP);
            unlink($archivoZIP);
            return Response::create($zipFile, 200, array("Content-Type" => "application/zip",
                        "content-disposition" => "attachment;filename=$archivoZIP"));
        } else
            return $this->redirect($this->generateUrl('favorito'));
    }

    /**
     * Crea una instancia la clase <b>FavoritoXML</b> luego de verificar que existen todas
     * las propiedades del sistema relacionadas con los favoritos. En caso de error lanza un 
     * mensaje a la vista.
     * 
     * @return FavoritoXML|boolean Devuelve una instancia de la clase <b>FavoritoXML</b> o 
     * <b>FALSE</b> en caso de error.
     */
    private function inicializarFavoritoXML() {
        $dm = $this->getDocumentManager();
        // Verificando que existen las propiedades del sistema
        $nombreArchivoZIP = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'favoritos_archivo_zip'))->getValor();
        $favoritoConfig = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'favoritos_configuracion'))->getValor();
        $favoritoTempExtractDir = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'favoritos_extraer_directorio'))->getValor();
        $favoritoTempDir = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'favoritos_directorio_temporal'))->getValor();
        $parentDir = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'favoritos_directorio'))->getValor();

        // Creando la instancia de FavoritoXML si todas las propiedades existen
        if ($nombreArchivoZIP && $favoritoConfig && $favoritoTempExtractDir && $favoritoTempDir && $parentDir) {
            $favoritoXML = new FavoritoXML($nombreArchivoZIP, $favoritoConfig, $favoritoTempDir, $favoritoTempExtractDir, $parentDir);
            return $favoritoXML;
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error crítico relacionado con las propiedades del sistema. Consulte este problema con el administrador.');
            return FALSE;
        }
    }

    /**
     * Importa la configuración de un conjunto de archivos favoritos desde un archivo ZIP  
     */
    public function importarAction(Request $request) {
        $formUpload = $this->createForm(new uploadType());
        $formUpload->bind($request);
        if ($formUpload->isValid()) {
            $zipFile = $formUpload->get('archivo')->getData();
            if ($zipFile instanceof UploadedFile && $zipFile->isValid()) {
                // Creando una instancia de la clase FavoritoXML
                $favoritoXML = $this->inicializarFavoritoXML();
                if ($favoritoXML) {
                    // Comprobando que el archivo tenga el formato ZIP
                    $type = $zipFile->getClientMimeType();
                    if ($type === 'application/zip') {
                        $zipFileName = $zipFile->getClientOriginalName();
                        try {
                            $zipFile->move($favoritoXML->getFavoritoTempDir(), $zipFileName);
                            $favoritos = $favoritoXML->importar($zipFileName);
                        } catch (Exception $exception) {
                            if ($exception instanceof FileException) {
                                $this->get('session')->getFlashBag()->add('danger', "No es posible acceder al directorio temporal de favoritos. <b>Permiso denegado!!!</b>'");
                            } else {
                                $this->get('session')->getFlashBag()->add('danger', $exception->getMessage());
                            }
                            return $this->redirect($this->generateUrl('favorito'));
                        }
                        $dm = $this->getDocumentManager();
                        foreach ($favoritos as $favorito) {
                            $dm->persist($favorito);
                        }
                        $dm->flush();
                        $this->get('session')->getFlashBag()->add('success', 'El archivo favorito ha sido importado con éxito');
                    } else {
                        $this->get('session')->getFlashBag()->add('warning', 'El archivo favorito que intenta importar debe cumplir con el formato (*.zip)');
                    }
                }
            } else {
                $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error inesperando durante la subida del archivo.');
            }
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error inesperando durante la subida del archivo.');
        }
        return $this->redirect($this->generateUrl('favorito'));
    }

    /**
     * Crea el formulario de eliminar configuración Favorita
     * 
     * @param String $id ID del Favorito 
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
