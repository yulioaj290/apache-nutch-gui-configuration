<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Document\archivo;
use ConfigOrion\ConfigOrionBundle\Document\modificacion;
use ConfigOrion\ConfigOrionBundle\Document\etiqueta;
use ConfigOrion\ConfigOrionBundle\Form\archivoType;
use ConfigOrion\ConfigOrionBundle\Form\archivoUpdateType;
use ConfigOrion\ConfigOrionBundle\Form\archivoUpdateDataType;
use ConfigOrion\ConfigOrionBundle\Form\archivoNewPropertyType;
use ConfigOrion\ConfigOrionBundle\Form\archivoNewGenericPropertyType;
use ConfigOrion\ConfigOrionBundle\Form\archivoDeleteGenericPropertyType;
use ConfigOrion\ConfigOrionBundle\Form\perfilAddType;
use ConfigOrion\ConfigOrionBundle\Form\archivarModificacionType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clase controladora que se encarga de la gestión de Archivos
 */
class archivoController extends Controller {

    /**
     * Muestra la lista de los archivos de configuración registrados en el sistema
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();

        $documents = $dm->getRepository('ConfigOrionBundle:archivo')->findAll();

        return $this->render('ConfigOrionBundle:archivo:index.html.twig', array('documents' => $documents));
    }

    /**
     * Muestra la vista para registrar un archivo de configuración
     *
     * @param String $parent Toma el valor "instancia" o "plugin"
     * @param String $id_parent ID de la instancia o plugin 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction($parent, $id_parent) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = new archivo();
        $document->setRuta($ $parent->getRuta());
        $form = $this->createForm(new archivoType(), $document);

        $form->get('descripcionModificacion')->setData(' ');

        if ($parent == 'plugin') {
            $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($ $parent);
            return $this->render('ConfigOrionBundle:archivo:new.html.twig', array(
                        'document' => $document,
                        $parent => $ $parent,
                        'instancia_item' => $instanciaItem,
                        'parent' => $parent,
                        'form' => $form->createView()
            ));
        }

        return $this->render('ConfigOrionBundle:archivo:new.html.twig', array(
                    'document' => $document,
                    $parent => $ $parent,
                    'parent' => $parent,
                    'form' => $form->createView()
        ));
    }

    /**
     * Registra un nuevo archivo de configuración
     *
     * @param String $parent Toma el valor "instancia" o "plugin"
     * @param String $id_parent ID de la instancia o plugin
     * @param Request $request Datos del formulario
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction($parent, $id_parent, Request $request) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = new archivo();
        $form = $this->createForm(new archivoType(), $document);
        $form->bind($request);

        if ($form->isValid()) {

            // Obteniendo la ruta del archivo
            $file = $form->get('ruta')->getData() . '/' . $form->get('nombre')->getData();
            if (strtolower($form->get('formato')->getData()) != "null") {
                $file .= '.' . strtolower($form->get('formato')->getData());
            }

            // Determinando el tipo MIME del archivo
            if (is_file($file)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE); // Inicializando el recurso                             
                $tipoMime = finfo_file($finfo, $file);  // Obtiene el tipo MIME para un fichero específico          
                finfo_close($finfo);   // Destruyendo el recurso            
            }

            // Obteniendo el archivo si existe
            $existeArchivo = $dm->getRepository('ConfigOrionBundle:archivo')->findOneBy(array('ruta' => $form->get('ruta')->getData(), 'nombre' => $form->get('nombre')->getData(), 'formato' => $form->get('formato')->getData()));

            // Obteniendo directorio de despliegue de instancias
            $directorioInstancias = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();

            if (strncasecmp($directorioInstancias, $form->get('ruta')->getData(), strlen($directorioInstancias)) != 0) { // Verificando que la ruta este dentro del directorio de despliegue de instancias
                $this->get('session')->getFlashBag()->add('danger', 'Ud. no tiene acceso a este directorio. Verifique la ruta entrada!!!');
            } elseif (!is_file($file)) { // Verificando que el archivo existe
                $this->get('session')->getFlashBag()->add('danger', 'El archivo especificado no existe.');
            } elseif (!is_writable($file)) {
                $this->get('session')->getFlashBag()->add('danger', 'No es posible acceder al archivo que intenta registrar. <b>Permiso denegado!!!</b>');
            } elseif ($existeArchivo != '') { // Verificando si el archivo ya se ha registrado
                $this->get('session')->getFlashBag()->add('danger', 'El archivo que intenta registrar ya existe.');
            } elseif (($form->get('formato')->getData() === "TXT" || $form->get('formato')->getData() === "NULL") && $tipoMime != 'text/plain') {
                $this->get('session')->getFlashBag()->add('danger', 'El archivo que se ha intentado registrar no es realmente de tipo "TXT" o "Texto Plano". Verifique!!!');
            } elseif ($form->get('formato')->getData() === "XML" && $tipoMime != 'application/xml') {
                $this->get('session')->getFlashBag()->add('danger', 'El archivo que se ha intentado registrar no es realmente de tipo "XML". Verifique!!!');
            } elseif (substr($form->get('ruta')->getData(), strlen($form->get('ruta')->getData()) - 1, 1) == '/') {  // Verificando que la ruta no termine en /
                $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede terminar con el caracter "/".');
            } elseif (preg_match('/\/\//', $form->get('ruta')->getData()) == 1) {  // Verificando que la ruta no posea dos /
                $this->get('session')->getFlashBag()->add('danger', 'La ruta no puede contener dos caracteres "/" seguidos.');
            } elseif (strcmp($ $parent->getRuta() . '/', substr($form->get('ruta')->getData(), 0, strlen($ $parent->getRuta()) + 1)) != 0 && strlen($form->get('ruta')->getData()) != strlen($ $parent->getRuta())) {  // Verificando que el plugin pertenezca a la instancia
                $this->get('session')->getFlashBag()->add('danger', 'La parte inicial de la ruta del archivo debe coincidir con la ruta de la instancia a la que pertenece. Verifique!!!');
            } else {
                $content = file_get_contents($file);
                $document->setContenido($content);

                $dm->persist($document);
                $ $parent->addArchivo($document);
                $dm->flush();

                // Mostrando mensaje
                $this->get('session')->getFlashBag()->add('success', 'El archivo se ha registrado correctamente.');
                return $this->redirect($this->generateUrl('archivo_show', array('id' => $document->getId(), 'parent' => $parent, 'id_parent' => $ $parent->getId())));
            }
        }

        if ($parent == 'plugin') {
            $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($ $parent);
            return $this->render('ConfigOrionBundle:archivo:new.html.twig', array(
                        'document' => $document,
                        $parent => $ $parent,
                        'instancia_item' => $instanciaItem,
                        'parent' => $parent,
                        'form' => $form->createView()
            ));
        }

        return $this->render('ConfigOrionBundle:archivo:new.html.twig', array(
                    'document' => $document,
                    $parent => $ $parent,
                    'parent' => $parent,
                    'form' => $form->createView()
        ));
    }

    /**
     * Muestra la vista de Ver Datos de Archivo
     *
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin 
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function showAction($parent, $id_parent, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);

        if ($parent == 'plugin') {
            $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($ $parent);
            return $this->render('ConfigOrionBundle:archivo:show.html.twig', array(
                        'document' => $document,
                        $parent => $ $parent,
                        'instancia_item' => $instanciaItem,
                        'parent' => $parent,
                        'delete_form' => $deleteForm->createView(),));
        }

        return $this->render('ConfigOrionBundle:archivo:show.html.twig', array(
                    'document' => $document,
                    $parent => $ $parent,
                    'parent' => $parent,
                    'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Muestra la vista de Editar Archivo
     *
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin 
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($parent, $id_parent, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        // Obteniendo la ruta del archivo
        $extension = '';
        $file = $document->getRuta() . '/' . $document->getNombre();
        if (strtolower($document->getFormato()) != "null") {
            $extension = '.' . strtolower($document->getFormato());
            $file .= $extension;
        }
        $archivoNombre = $document->getNombre() . $extension;

        // Verificando que el archivo existe
        if (!is_writable($file)) {
            $this->get('session')->getFlashBag()->add('warning', 'No es posible acceder al archivo <b><i>"' . $archivoNombre . '"</i></b>. Verifique que este exista y tenga los permisos necesarios.');
            if ($parent == 'plugin')
                return $this->redirect($this->generateUrl('plugin_admin', array('id' => $id_parent)));
            else
                return $this->redirect($this->generateUrl('instancia_admin', array('id' => $id_parent)));
        } else {
            // Obteniendo el contenido del archivo real
            $content = file_get_contents($file);

            if (strcasecmp(hash('md5', $document->getContenido()), hash('md5', $content)) != 0) {
                // Verificando que el contenido del archivo real coincida con el registrado
                $this->get('session')->getFlashBag()->add('danger', 'El contenido del archivo registrado no coincide con el real. Continue bajo su responsabilidad.');
            }

            if (!$document) {
                throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
            }


            $editForm = $this->createForm(new archivoUpdateType(), $document);
            $newPropertyForm = $this->createForm(new archivoNewPropertyType(), $document);
            $newGenericPropertyForm = $this->createForm(new archivoNewGenericPropertyType(), $document);
            $deleteGenericPropertyForm = $this->createForm(new archivoDeleteGenericPropertyType(), $document);
            $deleteForm = $this->createDeleteForm($id);

            // Obteniendo la propiedad del sistema con nombre de archivo principal
            $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();

            $html = '';
            if ($document->getFormato() == 'XML') {
                $file = $document->getRuta() . '/' . $document->getNombre() . '.' . \strtolower($document->getFormato());
                $xml = file_get_contents($file);
                $DOM = new \DOMDocument('1.0', 'utf-8');
                $DOM->encoding = 'utf-8';
                if (!@$DOM->loadXML($xml)) {
                    $this->get('session')->getFlashBag()->add('danger', 'El archivo contiene inconsistencias y no se pudo cargar.');
                    return $this->redirect($this->generateUrl('archivo_show', array('id' => $document->getId(), 'parent' => $parent, 'id_parent' => $ $parent->getId())));
                }
                $datos = $DOM->childNodes;

                if (strcasecmp($archivo_principal, \strtolower($document->getNombre() . '.' . $document->getFormato())) == 0) {
                    $html .= utf8_encode($this->mostrarHtmlArchivoPrincipal($datos, $id_parent, $parent, '0', $id));
                } else {
                    $html .= utf8_encode($this->mostrarHtmlArchivoXML($datos));
                }
            }
            $perfilesForm = $this->createForm(new perfilAddType());

            if ($parent == 'plugin') {
                $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($ $parent);
                return $this->render('ConfigOrionBundle:archivo:edit.html.twig', array(
                            'document' => $document,
                            $parent => $ $parent,
                            'parent' => $parent,
                            'archivo_principal' => $archivo_principal,
                            'html' => $html,
                            'instancia_item' => $instanciaItem,
                            'edit_form' => $editForm->createView(),
                            'new_property_form' => $newPropertyForm->createView(),
                            'new_generic_property_form' => $newGenericPropertyForm->createView(),
                            'deleteGenericPropertyForm' => $deleteGenericPropertyForm->createView(),
                            'delete_form' => $deleteForm->createView(),
                ));
            }

            return $this->render('ConfigOrionBundle:archivo:edit.html.twig', array(
                        'document' => $document,
                        $parent => $ $parent,
                        'parent' => $parent,
                        'archivo_principal' => $archivo_principal,
                        'html' => $html,
                        'edit_form' => $editForm->createView(),
                        'new_property_form' => $newPropertyForm->createView(),
                        'new_generic_property_form' => $newGenericPropertyForm->createView(),
                        'deleteGenericPropertyForm' => $deleteGenericPropertyForm->createView(),
                        'delete_form' => $deleteForm->createView(),
            ));
        }
    }

    /**
     * Actualiza los datos de un Archivo
     * 
     * Inserta o modifica en la Base de Datos las nuevas Etiquetas creadas 
     * 
     * Registra las Modificaciones correspondientes a cada propiedad de
     * configuración que se le halla cambiado el valor
     *
     * @param Request $request Datos de la solicitud
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction($parent, $id_parent, Request $request, $id) {
        // Inicializando el Administrador de Documentos
        $ERROR = FALSE;
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
        }

        $documentAnterior = clone $document;

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new archivoUpdateType(), $document);
        $editForm->bind($request);
        $newPropertyForm = $this->createForm(new archivoNewPropertyType());
        $newPropertyForm->bind($request);
        $newGenericPropertyForm = $this->createForm(new archivoNewGenericPropertyType());
        $newGenericPropertyForm->bind($request);
        $deleteGenericPropertyForm = $this->createForm(new archivoDeleteGenericPropertyType());
        $deleteGenericPropertyForm->bind($request);

        // Obteniendo la ruta del archivo
        $extension = '';
        $rutaFile = $document->getRuta() . '/' . $document->getNombre();
        if (\strtolower($document->getFormato()) != 'null') {
            $extension = '.' . \strtolower($document->getFormato());
            $rutaFile .= $extension;
        }

        $archivoNombre = $document->getNombre() . $extension;

        if (!is_writable($rutaFile)) {
            $this->get('session')->getFlashBag()->add('warning', 'No es posible acceder al archivo <b><i>"' . $archivoNombre . '"</i></b>. Verifique que este exista y tenga los permisos necesarios.');
            if ($parent == 'plugin')
                return $this->redirect($this->generateUrl('plugin_admin', array('id' => $id_parent)));
            else
                return $this->redirect($this->generateUrl('instancia_admin', array('id' => $id_parent)));
        } else {
            // Obteniendo la propiedad del sistema con nombre de archivo principal
            $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();

            $descripcionField = trim($editForm->get('descripcionModificacion')->getData());
            if (!is_file($rutaFile)) {    // Verificando que el archivo existe
                $this->get('session')->getFlashBag()->add('danger', 'El archivo especificado no existe.');
            } elseif (empty($descripcionField)) {
                $this->get('session')->getFlashBag()->add('warning', 'El campo Descripción de la modificación es obligatorio.');
            } elseif ($editForm->isValid()) {
                if ($document->getFormato() == 'XML') {
                    // Inicializando el documento antes de modificarlo
                    $DOMAnterior = new \DOMDocument('1.0', 'utf-8');
                    $DOMAnterior->encoding = 'utf-8';
                    $DOMAnterior->preserveWhiteSpace = false;
                    $DOMAnterior->formatOutput = true;
                    $DOMAnterior->loadXML($documentAnterior->getContenido());

                    $xpathfAnterior = new \DOMXPath($DOMAnterior);

                    if (strcasecmp($archivo_principal, \strtolower($document->getNombre() . '.' . $document->getFormato())) == 0) {
                        // Obteniendo los datos enviados
                        $attrbs = $_POST['attrb'];
                        $paths = $_POST['path'];
                        $tags = $_POST['tag'];
                        $pathTags = $_POST['pathTag'];
                        $properties = $_POST['property'];

                        for ($i = 0; $i < \count($attrbs); $i++) {
                            if (!isset($pathTags[$i]) || !isset($paths[$i]) || !isset($attrbs[$i]) || !isset($properties[$i]) || !isset($tags[$i])) {
                                $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error al procesar los datos. Por favor, vuelva a intentarlo.');
                                $ERROR = TRUE;
                            } else {
                                if ($xpathfAnterior->query($paths[$i])->item(0)->nodeValue != $attrbs[$i]) {
                                    $valorAnteriorPropiedad = $xpathfAnterior->query($paths[$i])->item(0)->nodeValue;
                                    $xpathfAnterior->query($paths[$i])->item(0)->nodeValue = $attrbs[$i];

                                    //Guardando datos de la modificacion  
                                    $modificacion = new modificacion(new \DateTime(), $properties[$i], $paths[$i], $valorAnteriorPropiedad, $attrbs[$i], 'MODIFICAR', 'TRUE', $editForm->get('descripcionModificacion')->getData());

                                    $dm->persist($modificacion);
                                    $document->addModificacione($modificacion);
                                }

                                // Verificando si la etiqueta existe
                                $etiquetasDocument = $document->getEtiquetas();
                                $etiquetaObj = NULL;
                                foreach ($etiquetasDocument as $etiquetaDoc) {
                                    if ($etiquetaDoc->getRutaPropiedad() == $pathTags[$i]) {
                                        $etiquetaObj = $etiquetaDoc;
                                    }
                                }
                                // Guardando datos de etiquetas
                                if ($etiquetaObj == NULL && $tags[$i] != '') {
                                    $etiqueta = new etiqueta();
                                    $etiqueta->setPropiedad($properties[$i]);
                                    $etiqueta->setRutaPropiedad($pathTags[$i]);
                                    $etiqueta->setValor(html_entity_decode($tags[$i], null, "UTF-8"));

                                    $dm->persist($etiqueta);
                                    $document->addEtiqueta($etiqueta);
                                } elseif ($etiquetaObj != NULL && $tags[$i] != html_entity_decode($etiquetaObj->getValor(), null, "UTF-8") && $tags[$i] == '') {
                                    $document->removeEtiqueta($etiquetaObj);
                                    $dm->remove($etiquetaObj);
                                } elseif ($etiquetaObj != NULL && $tags[$i] != html_entity_decode($etiquetaObj->getValor(), null, "UTF-8")) {
                                    $etiquetaObj->setValor(html_entity_decode($tags[$i], null, "UTF-8"));
                                    $dm->persist($etiquetaObj);
                                }
                            }
                        }
                        // Guardando datos de la nueva propiedad insertada
                        if (!$ERROR) {
                            if (trim($newPropertyForm->get('nameNewProperty')->getData()) != '' && trim($newPropertyForm->get('valueNewProperty')->getData()) != '' && trim($newPropertyForm->get('descNewProperty')->getData()) != '') {
                                // Verificando que no exista la etiqueta <property> en el archivo
                                $existePropiedad = false;
                                for ($i = 0; $i < $xpathfAnterior->query('property', $xpathfAnterior->query('/configuration')->item(0))->length; $i++) {
                                    // Convirtiendo la <property> actual en un SimpleXML
                                    $simpleXMLChildNodes = simplexml_import_dom($xpathfAnterior->query('property', $xpathfAnterior->query('/configuration')->item(0))->item($i));
                                    if ($simpleXMLChildNodes->name == $newPropertyForm->get('nameNewProperty')->getData()) {
                                        $existePropiedad = true;
                                        $this->get('session')->getFlashBag()->add('warning', 'No se pudo insertar la propiedad "' . $newPropertyForm->get('nameNewProperty')->getData() . '" porque ya existe.');
                                    }
                                }
                                if (!$existePropiedad) {
                                    $newProperty = $DOMAnterior->createElement('property');
                                    $newPropertyName = $DOMAnterior->createElement('name');
                                    $newPropertyValue = $DOMAnterior->createElement('value');
                                    $newPropertyDesc = $DOMAnterior->createElement('description');
                                    $newPropertyName->nodeValue = $newPropertyForm->get('nameNewProperty')->getData();
                                    $newPropertyValue->nodeValue = $newPropertyForm->get('valueNewProperty')->getData();
                                    $newPropertyDesc->nodeValue = $newPropertyForm->get('descNewProperty')->getData();
                                    $newProperty->appendChild($newPropertyName);
                                    $newProperty->appendChild($newPropertyValue);
                                    $newProperty->appendChild($newPropertyDesc);
                                    $xpathfAnterior->query('/configuration')->item(0)->appendChild($newProperty);

                                    // Guardando datos de la modificacion  
                                    $valorActual = '<property>'
                                            . '<name>' . $newPropertyForm->get('nameNewProperty')->getData() . '</name>'
                                            . '<value>' . $newPropertyForm->get('valueNewProperty')->getData() . '</value>'
                                            . '<description>' . $newPropertyForm->get('descNewProperty')->getData() . '</description>'
                                            . '</property>';
                                    $modificacion = new modificacion(new \DateTime(), $newPropertyForm->get('nameNewProperty')->getData(), '', '', $valorActual, 'INSERTAR', 'TRUE', $editForm->get('descripcionModificacion')->getData() . '. Se ha adicionado la propiedad "' . $newPropertyForm->get('nameNewProperty')->getData() . '"');

                                    $dm->persist($modificacion);
                                    $document->addModificacione($modificacion);
                                    $this->get('session')->getFlashBag()->add('info', 'La nueva propiedad se ha insertado correctamente.');
                                }
                            } elseif (trim($newPropertyForm->get('nameNewProperty')->getData()) == '' && trim($newPropertyForm->get('valueNewProperty')->getData()) == '' && trim($newPropertyForm->get('descNewProperty')->getData()) == '') {
                                true;
                            } else {
                                $this->get('session')->getFlashBag()->add('warning', 'No se pudo insertar la propiedad porque ha dejado campos en blanco.');
                            }
                        }
                    } else {// Cualquier otro archivo XML
                        // Obteniendo los datos enviados
                        $attrbs = $_POST['attrb'];
                        $paths = $_POST['path'];

                        for ($i = 0; $i < \count($attrbs); $i++) {
                            if (!isset($paths[$i]) || !isset($attrbs[$i])) {
                                $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error al procesar los datos. Por favor, vuelva a intentarlo.');
                                $ERROR = TRUE;
                            } else {
                                if ($xpathfAnterior->query($paths[$i])->item(0)->nodeValue != $attrbs[$i]) {
                                    $valorAnteriorPropiedad = $xpathfAnterior->query($paths[$i])->item(0)->nodeValue;
                                    $namePropiedad = $xpathfAnterior->query($paths[$i])->item(0)->nodeName;
                                    $xpathfAnterior->query($paths[$i])->item(0)->nodeValue = $attrbs[$i];

                                    //Guardando datos de la modificacion  
                                    $modificacion = new modificacion(new \DateTime(), $namePropiedad, $paths[$i], $valorAnteriorPropiedad, $attrbs[$i], 'MODIFICAR', 'TRUE', $editForm->get('descripcionModificacion')->getData());

                                    $dm->persist($modificacion);
                                    $document->addModificacione($modificacion);
                                }
                            }
                        }

                        // Guardando datos de insercion de propiedad
                        if (!$ERROR) {
                            if (trim($newGenericPropertyForm->get('newGenericRuta')->getData()) != '' && trim($newGenericPropertyForm->get('newGenericProperty')->getData()) != '') {
                                libxml_use_internal_errors(true);
                                $sxe = simplexml_load_string($newGenericPropertyForm->get('newGenericProperty')->getData());
                                if ($sxe === false) {
                                    $errors = libxml_get_errors();
                                    $this->get('session')->getFlashBag()->add('danger', 'No se pudo insertar la propiedad porque hay un error en la l&iacute;nea ' . $errors[0]->line . '. Verifique!!!');
                                } else {
                                    // Importando XML desde SimpleXML a DOMDocument
                                    $dom_sxe = dom_import_simplexml($sxe);
                                    $dom_sxe = $DOMAnterior->importNode($dom_sxe, TRUE);
                                    // Verificando que la ruta sea correcta
                                    $DOMNodeListRuta = $xpathfAnterior->query($newGenericPropertyForm->get('newGenericRuta')->getData());
                                    if ($DOMNodeListRuta->length > 0) {
                                        // Adicionando hijo
                                        $DOMNodeListRuta->item(0)->appendChild($dom_sxe);

                                        // Guardando datos de la modificacion  
                                        $modificacion = new modificacion(new \ DateTime(), $xpathfAnterior->query($newGenericPropertyForm->get('newGenericRuta')->getData())->item(0)->lastChild->nodeName, '', '', $newGenericPropertyForm->get('newGenericProperty')->getData(), 'INSERTAR', 'TRUE', $editForm->get('descripcionModificacion')->getData() . '. Se ha adicionado la propiedad "' . $xpathfAnterior->query($newGenericPropertyForm->get('newGenericRuta')->getData())->item(0)->lastChild->nodeName . '"');

                                        $dm->persist($modificacion);
                                        $document->addModificacione($modificacion);
                                        $this->get('session')->getFlashBag()->add('info', 'La nueva propiedad se ha insertado correctamente.');
                                    } else {
                                        $this->get('session')->getFlashBag()->add('danger', 'La ruta de la propiedad padre no es correcta. Verifique!!!');
                                    }
                                }
                            } elseif (trim($newGenericPropertyForm->get('newGenericRuta')->getData()) == '' && trim($newGenericPropertyForm->get('newGenericProperty')->getData()) == '') {
                                true;
                            } else {
                                $this->get('session')->getFlashBag()->add('warning', 'No se pudo insertar la propiedad porque ha dejado campos en blanco.');
                            }
                        }
                    }

                    // Salvando datos en el sistema y en el archivo real
                    if (!$ERROR) {
                        $DOMAnterior->save($rutaFile);
                        // Decodificando caracteres
                        $newContent = html_entity_decode(file_get_contents($rutaFile), null, "UTF-8");
                        // Guardando contenido decodificado
                        $file = fopen($rutaFile, "w");
                        fwrite($file, $newContent);
                        fclose($file);
                        // Actualizando el contenido decodificado en el sistema
                        $document->setContenido($newContent);
                    }
                } else {
                    // Guardando canbios fisicos en el archivo
                    if (strcasecmp(hash('md5', $documentAnterior->getContenido()), hash('md5', $editForm->get('contenido')->getData())) != 0) {
                        $file = fopen($rutaFile, "w");
                        fwrite($file, $editForm->get('contenido')->getData());
                        fclose($file);

                        //Guardando datos de la modificacion  
                        $modificacion = new modificacion(new \DateTime(), 'Archivo: ' . $document->getNombre(), ' ', $documentAnterior->getContenido(), $editForm->get('contenido')->getData(), 'MODIFICAR', 'TRUE', $editForm->get('descripcionModificacion')->getData());

                        $dm->persist($modificacion);
                        $document->addModificacione($modificacion);
                    }
                }
                if (!$ERROR) {
                    // Persistiendo los cambios
                    $dm->persist($document);
                    $dm->flush();

                    // Mostrando mensaje
                    $this->get('session')->getFlashBag()->add('success', 'El archivo se ha editado correctamente.');

                    return $this->redirect($this->generateUrl('archivo_show', array('id' => $document->getId(), 'parent' => $parent, 'id_parent' => $ $parent->getId())));
                }
            }

            // Volviendo a contruir el formulario si algo ha fallado
            $html = '';
            if ($documentAnterior->getFormato() == 'XML') {
                $file = $documentAnterior->getRuta() . '/' . $documentAnterior->getNombre() . '.' . \strtolower($documentAnterior->getFormato());
                $xml = file_get_contents($file);
                $DOM = new \DOMDocument('1.0', 'utf-8');
                $DOM->encoding = 'utf-8';
                $DOM->loadXML($xml);
                $datos = $DOM->childNodes;

                if (strcasecmp($archivo_principal, \strtolower($documentAnterior->getNombre() . '.' . $documentAnterior->getFormato())) == 0) {
                    $html .= utf8_encode($this->mostrarHtmlArchivoPrincipal($datos, $id_parent, $parent, '0', $id));
                } else {
                    $html .= utf8_encode($this->mostrarHtmlArchivoXML($datos));
                }
            }

            if ($parent == 'plugin') {
                $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($ $parent);
                return $this->render('ConfigOrionBundle:archivo:edit.html.twig', array(
                            'document' => $documentAnterior,
                            $parent => $ $parent,
                            'instancia_item' => $instanciaItem,
                            'parent' => $parent,
                            'archivo_principal' => $archivo_principal,
                            'html' => $html,
                            'edit_form' => $editForm->createView(),
                            'new_property_form' => $newPropertyForm->createView(),
                            'deleteGenericPropertyForm' => $deleteGenericPropertyForm->createView(),
                            'new_generic_property_form' => $newGenericPropertyForm->createView(),
                            'delete_form' => $deleteForm->createView(),
                ));
            }

            return $this->render('ConfigOrionBundle:archivo:edit.html.twig', array(
                        'document' => $documentAnterior,
                        $parent => $ $parent,
                        'parent' => $parent,
                        'archivo_principal' => $archivo_principal,
                        'html' => $html,
                        'edit_form' => $editForm->createView(),
                        'new_property_form' => $newPropertyForm->createView(),
                        'deleteGenericPropertyForm' => $deleteGenericPropertyForm->createView(),
                        'new_generic_property_form' => $newGenericPropertyForm->createView(),
                        'delete_form' => $deleteForm->createView(),
            ));
        }
    }

    /**
     * Muestra la vista de Editar Archivo
     *
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin 
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editDataAction($parent, $id_parent, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        // Obteniendo la ruta del archivo
        $extension = '';
        $file = $document->getRuta() . '/' . $document->getNombre();
        if (strtolower($document->getFormato()) != "null") {
            $extension = '.' . \strtolower($document->getFormato());
            $file .= $extension;
        }

        $archivoNombre = $document->getNombre() . $extension;

        if (!is_writable($file)) {
            $this->get('session')->getFlashBag()->add('warning', 'No es posible acceder al archivo <b><i>"' . $archivoNombre . '"</i></b>. Verifique que este exista y tenga los permisos necesarios.');
            if ($parent == 'plugin')
                return $this->redirect($this->generateUrl('plugin_admin', array('id' => $id_parent)));
            else
                return $this->redirect($this->generateUrl('instancia_admin', array('id' => $id_parent)));
        } else {
            // Obteniendo el contenido del archivo real
            $content = file_get_contents($file);

            // Verificando que el archivo existe
            if (!is_file($file)) {
                $this->get('session')->getFlashBag()->add('danger', 'El archivo especificado no existe.');
            } elseif (strcasecmp(hash('md5', $document->getContenido()), hash('md5', $content)) != 0) {
                // Verificando que el contenido del archivo real coincida con el registrado
                $this->get('session')->getFlashBag()->add('danger', 'El contenido del archivo registrado no coincide con el real. Continue bajo su responsabilidad.');
            }

            if (!$document) {
                throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
            }

            $editForm = $this->createForm(new archivoUpdateDataType(), $document);
            $deleteForm = $this->createDeleteForm($id);


            if ($parent == 'plugin') {
                $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($ $parent);
                return $this->render('ConfigOrionBundle:archivo:editData.html.twig', array(
                            'document' => $document,
                            $parent => $ $parent,
                            'parent' => $parent,
                            'instancia_item' => $instanciaItem,
                            'edit_form' => $editForm->createView(),
                            'delete_form' => $deleteForm->createView(),
                ));
            }

            return $this->render('ConfigOrionBundle:archivo:editData.html.twig', array(
                        'document' => $document,
                        $parent => $ $parent,
                        'parent' => $parent,
                        'edit_form' => $editForm->createView(),
                        'delete_form' => $deleteForm->createView(),
            ));
        }
    }

    /**
     * Actualiza los datos de un Archivo
     * 
     * Inserta o modifica en la Base de Datos las nuevas Etiquetas creadas 
     * 
     * Registra las Modificaciones correspondientes a cada propiedad de
     * configuración que se le halla cambiado el valor
     *
     * @param Request $request Datos de la solicitud
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateDataAction($parent, $id_parent, Request $request, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('El archivo seleccionado ya no se encuentra disponible en la base de datos.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new archivoUpdateDataType(), $document);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            // Persistiendo los cambios
            $dm->persist($document);
            $dm->flush();

            // Mostrando mensaje
            $this->get('session')->getFlashBag()->add('success', 'Los datos del archivo se han editado correctamente.');

            return $this->redirect($this->generateUrl('archivo_show', array('id' => $document->getId(), 'parent' => $parent, 'id_parent' => $ $parent->getId())));
        }

        if ($parent == 'plugin') {
            $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($ $parent);
            return $this->render('ConfigOrionBundle:archivo:editData.html.twig', array(
                        'document' => $document,
                        $parent => $ $parent,
                        'parent' => $parent,
                        'instancia_item' => $instanciaItem,
                        'edit_form' => $editForm->createView(),
                        'delete_form' => $deleteForm->createView(),
            ));
        }

        return $this->render('ConfigOrionBundle:archivo:editData.html.twig', array(
                    'document' => $document,
                    $parent => $ $parent,
                    'parent' => $parent,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Construye la vista especial dedicada al Archivo Principal de configuración de Nutch
     * a medida que se analiza (parsea) la estructura del mismo.
     * 
     * @global integer $indexMostrarHTML Indice para diferenciar cada elemento encontrado
     * @global integer $countAccordion Contador para los acordions de agrupamiento de las propiedades de configuracion
     * @global string $nombrePropiedad Nombre de la propiedad de configuracion
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param integer $id_parent ID del padre del archivo analizado
     * @param string $name_parent Nombre del padre del archivo analizado
     * @param integer $no_property Numero de la propiedad actual
     * @param integer $id_archivo ID del archivo analizado
     * @return string Retorna una estructura HTML con formularios que 
     * representa cada campo modificable en el archivo principal de configuracion
     */
    function mostrarHtmlArchivoPrincipal($parent, $id_parent, $name_parent, $no_property, $id_archivo) {
        // Inicializando el Administrador de documentos
        $dm = $this->getDocumentManager();

        global $indexMostrarHTML;
        global $countAccordion;
        global $nombrePropiedad;

        $html = '';
        foreach ($parent as $dato) {
            if ($dato->nodeName != 'xml-stylesheet' && $dato->nodeName != 'xml') {
                if ($dato->hasChildNodes()) {
                    $childNodes = $dato->childNodes;
                    if ($dato->nodeName == 'property') {
                        // Importando desde DOM a SimpleXML
                        $simpleXMLChildNodes = simplexml_import_dom($dato);
                        // Formulario de etiqueta
                        $archivo = $dm->getRepository('ConfigOrionBundle:archivo')->findOneById($id_archivo);
                        $etiquetasArchivo = $archivo->getEtiquetas();
                        $valueEtiqueta = 'value="" placeholder="Pulse aqu&iacute; para crear etiqueta..."';
                        $tooltipEtiqueta = 'Pulse aqu&iacute; para crear etiqueta...';
                        if (!$etiquetasArchivo->isEmpty()) {
                            foreach ($etiquetasArchivo as $etiqueta) {
                                if ($etiqueta->getRutaPropiedad() == $dato->getNodePath()) {
                                    $valueEtiqueta = 'value="' . utf8_decode($etiqueta->getValor()) . '"';
                                    $tooltipEtiqueta = utf8_decode($etiqueta->getValor());
                                }
                            }
                        }
                        // Obtengo el numero de la <property> a traves de su ruta en XML
                        $no_property = $this->get_string_between($dato->getNodePath(), 'property[', ']');
                        $html .= '<div class="panel panel-default">
                                  <div class="panel-heading">
                                    <h4 class="panel-title">
                                      <a data-toggle="collapse" data-parent="accordionProperty" href="#collapseAcc' . $countAccordion . '">
                                        <span class="glyphicon glyphicon-chevron-down"></span>&nbsp;&nbsp;
                                        ' . $simpleXMLChildNodes->name . '
                                      </a>
                                      <div class="panel-tags">
                                        <span class="glyphicon glyphicon-tags"></span><input type="text" class="input-same-back" name="tag[' . $indexMostrarHTML . ']" maxlength="105" ' . $valueEtiqueta . ' rel="tooltip" data-placement="top" data-original-title="' . $tooltipEtiqueta . '" />
                                            <input type="hidden" name="pathTag[' . $indexMostrarHTML . ']" value="' . $dato->getNodePath() . '" />
                                      </div>
                                      <div class="element-float-right">'
                                . /* <a href="#" data-toggle="modal" data-target="#modalAdd" onclick="setProperty(\'' . $simpleXMLChildNodes->name . '\',\'' . $dato->getNodePath() . '\', \'' . $simpleXMLChildNodes->value . '\', \'' . $id_archivo . ');" class="glyphicon glyphicon-share" rel="tooltip" data-placement="top" data-original-title="A&ntilde;adir a perfil" >
                                  </a> */
                                '<a href="#" data-toggle="modal" data-target="#modalDeleteProperty" onclick="confirmDeleteProperty(\'' . $no_property . '\');" class="glyphicon glyphicon-remove-circle" rel="tooltip" data-placement="top" data-original-title="Eliminar propiedad" >
                                        </a>
                                      </div>
                                    </h4>
                                  </div>
                                  <div id="collapseAcc' . $countAccordion . '" class="panel-collapse collapse">'
                                . '<div class="panel-body">';
                        $html .= $this->mostrarHtmlArchivoPrincipal($childNodes, $id_parent, $name_parent, $no_property, $id_archivo);
                        $html .= '</div></div></div>';
                        $indexMostrarHTML++;
                        $countAccordion++;
                    } else {
                        $html .= $this->mostrarHtmlArchivoPrincipal($childNodes, $id_parent, $name_parent, $no_property, $id_archivo);
                    }
                } elseif (!$dato->hasChildNodes() && $dato->nodeName == 'value') {
                    if (strlen($dato->nodeValue) > 80) {
                        $html .= '<textarea style="width: 450px; height: 100px;" name="attrb[' . $indexMostrarHTML . ']" id="' . $dato->parentNode->nodeName . '" >' . utf8_decode($dato->nodeValue) . '</textarea>';
                    } else {
                        $html .= '<input style="width: 450px;" type="text" name="attrb[' . $indexMostrarHTML . ']" id="' . $dato->parentNode->nodeName . '" value="' . utf8_decode($dato->nodeValue) . '" />';
                    }
                    $html .= '<input type="hidden" name="path[' . $indexMostrarHTML . ']" value="' . $dato->getNodePath() . '" />';
                } else {
                    if ($dato->nodeName === '#text' && !(\strcmp(\ substr($dato->getNodePath(), \strlen($dato->getNodePath()) - 1, 1), "]") === 0)) {
                        if ($dato->parentNode->nodeName == 'name') {
                            $html .= '<label style="text-transform:capitalize;" for="' . $dato->nodeValue . '">' . $dato->nodeValue . ":</label>";
                            $html .= '<input type="hidden" name="property[' . $indexMostrarHTML . ']" value="' . $dato->nodeValue . '" />';
                        } elseif ($dato->parentNode->nodeName == 'value') {
                            if (strlen($dato->nodeValue) > 80) {
                                $html .= '<textarea style="width: 450px; height: 100px;" name="attrb[' . $indexMostrarHTML . ']" id="' . $dato->parentNode->nodeName . '" >' . utf8_decode($dato->nodeValue) . '</textarea>';
                            } else {
                                $html .= '<input style="width: 450px;" type="text" name="attrb[' . $indexMostrarHTML . ']" id="' . $dato->parentNode->nodeName . '" value="' . utf8_decode($dato->nodeValue) . '" />';
                            }
                            $html .= '<input type="hidden" name="path[' . $indexMostrarHTML . ']" value="' . $dato->getNodePath() . '" />';
                        } elseif ($dato->parentNode->nodeName == 'description') {
                            $html .= '<p>' . utf8_decode($dato->nodeValue) . '</p>';
                        }
                    }
                }
            }
        }
        return $html;
    }

    /**
     * Construye la vista generica dedicada a los Archivos de configuracion XML 
     * 
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @return string Retorna una estructura HTML con formularios que 
     * representa cada campo modificable en los archivos de configuracion XML
     */
    function mostrarHtmlArchivoXML($parent) {
        global $indexMostrarHTML;
        $html = '';
        foreach ($parent as $dato) {
            if ($dato->nodeName != 'xml-stylesheet' && $dato->nodeName != 'xml') {

                if ($dato->hasChildNodes()) {
                    $childNodes = $dato->childNodes;
                    $html .= '<div class="xml-indent">';
                    $html .= '<div class="xml-indent-head">';
                    $html .= '<a href="#" onclick="newGenericProperty(\'' . $dato->getNodePath() . '\');" class="glyphicon glyphicon-plus-sign" rel="tooltip" data-placement="top" data-original-title="Insertar etiqueta dentro" ></a><br />';
                    $html .= '<a href="#" class="glyphicon glyphicon-remove-circle" rel="tooltip" data-placement="top" data-original-title="Eliminar etiqueta XML" data-toggle="modal" data-target="#modalDeleteGenericProperty" onclick="deleteGenericProperty(\'' . $dato->getNodePath() . '\');"></a>';
                    $html .= '</a>';
                    $html .= '</div>';
                    $html .= '<div class="xml-indent-content"><span class="token-blue">&lt;' . $dato->nodeName . '</span>';
                    if ($dato->hasAttributes()) {
                        $attrs = $dato->attributes;
                        foreach ($attrs as $attr) {
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-green">' . $attr->nodeName . '</span>="';
                            $html .= '<input class="input-xml-atributos" style="width: ' . (8 * strlen($attr->nodeValue) ) . 'px;" type="text" name="attrb[' . $indexMostrarHTML . ']" value="' . utf8_decode($attr->nodeValue) . '" />';
                            $html .= '<input type="hidden" name="path[' . $indexMostrarHTML . ']" value="' . $attr->getNodePath() . '" />';
                            $html .= '"';
                            $indexMostrarHTML++;
                        }
                    }
                    $html .= '<span class="token-blue">&gt;</span>';
                    $html .= $this->mostrarHtmlArchivoXML($childNodes);
                    $html .= '<span class="token-blue">&lt;/' . $dato->nodeName . '&gt;</span></div>';
                    $html .= '<div class="clearboth"></div></div>';
                } elseif (!$dato->hasChildNodes() && $dato->nodeName != '#text' && $dato->nodeName != '#comment') {
                    $html .= '<div class="xml-indent">';
                    $html .= '<div class="xml-indent-head">';
                    $html .= '<a href="#" onclick="newGenericProperty(\'' . $dato->getNodePath() . '\');" class="glyphicon glyphicon-plus-sign" rel="tooltip" data-placement="top" data-original-title="Insertar etiqueta dentro" ></a><br />';
                    $html .= '<a href="#" class="glyphicon glyphicon-remove-circle" rel="tooltip" data-placement="top" data-original-title="Eliminar etiqueta XML" data-toggle="modal" data-target="#modalDeleteGenericProperty" onclick="deleteGenericProperty(\'' . $dato->getNodePath() . '\');"></a>';
                    $html .= '</div>';
                    $html .= '<div class="xml-indent-content"><span class="token-blue">&lt;' . $dato->nodeName . '</span>';
                    if ($dato->hasAttributes()) {
                        $attrs = $dato->attributes;
                        foreach ($attrs as $attr) {
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-green">' . $attr->nodeName . '</span>="';
                            $html .= '<input class="input-xml-atributos" style="width: ' . (8 * strlen($attr->nodeValue) ) . 'px;" type="text" name="attrb[' . $indexMostrarHTML . ']" value="' . utf8_decode($attr->nodeValue) . '" />';
                            $html .= '<input type="hidden" name="path[' . $indexMostrarHTML . ']" value="' . $attr->getNodePath() . '" />';
                            $html .= '"';
                            $indexMostrarHTML++;
                        }
                    }
                    $html .= '<span class="token-blue">&gt;</span>';
                    $html .= '<div class="no-border">';
                    $html .= '<input class="input-xml-text" type="text" name="attrb[' . $indexMostrarHTML . ']" id="' . $dato->parentNode->nodeName . '" value="' . utf8_decode($dato->nodeValue) . '" />';
                    $html .= '<input type="hidden" name="path[' . $indexMostrarHTML . ']" value="' . $dato->getNodePath() . '" />';
                    $html .= '</div>';
                    $html .= '<span class="token-blue">&lt;/' . $dato->nodeName . '&gt;</span></div>';
                    $html .= '<div class="clearboth"></div></div>';
                    $indexMostrarHTML++;
                } else {
                    if ($dato->nodeName === '#text' && !(\strcmp(\ substr($dato->getNodePath(), \strlen($dato->getNodePath()) - 1, 1), "]") === 0)) {
                        $html .= '<div class="no-border">';
                        if (strlen($dato->nodeValue) > 80) {
                            $html .= '<textarea class="input-xml-text" height: 100px;" name="attrb[' . $indexMostrarHTML . ']" id="' . $dato->parentNode->nodeName . '" >' . utf8_decode($dato->nodeValue) . '</textarea>';
                        } else {
                            $html .= '<input class="input-xml-text" type="text" name="attrb[' . $indexMostrarHTML . ']" id="' . $dato->parentNode->nodeName . '" value="' . utf8_decode($dato->nodeValue) . '" />';
                        }
                        $html .= '<input type="hidden" name="path[' . $indexMostrarHTML . ']" value="' . $dato->getNodePath() . '" />';
                        $html .= '</div>';
                        $indexMostrarHTML++;
                    }
                }
            }
        }
        return $html;
    }

    /**
     * Elimina del sistema un archivo de configuración
     *
     * @param Request $request Datos de la solicitud
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction($parent, $id_parent, Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        if ($form->isValid()) {
            $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('No se encontró el archivo relacionado en la base de datos.');
            }

            $ $parent->removeArchivo($document);
            $dm->remove($document);
            $dm->flush();
        }

        // Mostrando mensaje
        $this->get('session')->getFlashBag()->add('success', 'El archivo se ha eliminado correctamente.');
        return $this->redirect($this->generateUrl($parent . '_admin', array('id' => $ $parent->getId())));
    }

    /**
     * Muestra la vista de administración de modificaciones de un archivo
     *
     * @param String $id ID del archivo de configuración
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function adminAction($id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $formArchivar = $this->createForm(new archivarModificacionType());
        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        // Obteniendo propiedad del sistema con nombre de archivo principal
        $archivo_principal = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'archivo_principal'))->getValor();

        if (!$document) {
            throw $this->createNotFoundException('No se encontró el archivo relacionado en la base de datos.');
        }

        // Determinando el padre del archivo
        $parent = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByArchivo($document);
        $instanciaItem = $parent;
        $tipoParent = 'instancia';

        if (!($parent instanceof \ConfigOrion\ConfigOrionBundle\Document\instancia)) {
            $parent = $dm->getRepository('ConfigOrionBundle:plugin')->getPluginByArchivo($document);
            $instanciaItem = $dm->getRepository('ConfigOrionBundle:instancia')->getInstanciaByPlugin($parent);
            $tipoParent = 'plugin';
        }

        $modificaciones = $document->getModificaciones();
        $etiquetas = $document->getEtiquetas();

        $perfilesForm = $this->createForm(new perfilAddType());


        return $this->render('ConfigOrionBundle:archivo:admin.html.twig', array(
                    'archivo_parent' => $tipoParent,
                    'archivo_id_parent' => $parent->getId(),
                    'instancia_item' => $instanciaItem,
                    'document' => $document,
                    'archivo_principal' => $archivo_principal,
                    'modificaciones' => $modificaciones,
                    'etiquetas' => $etiquetas,
                    'formArchivar' => $formArchivar->createView(),
                    'perfilesForm' => $perfilesForm->createView()));
    }

    /**
     * Elimina una propiedad (property) del archivo principal de configuracion
     * 
     * @param integer $no_property Numero de la propiedad que se elimina  
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deletePropertyMainFileAction($parent, $id_parent, $no_property, $id) {
        settype($no_property, "int");
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No se encontró el archivo relacionado en la base de datos.');
        }

        // Obteniendo la ruta del archivo
        $extension = '';
        $rutaFile = $document->getRuta() . '/' . $document->getNombre();
        if (\strtolower($document->getFormato()) != "null") {
            $extension = '.' . \strtolower($document->getFormato());
            $rutaFile .= $extension;
        }

        $archivoNombre = $document->getNombre() . $extension;

        if (!is_writable($rutaFile)) {
            $this->get('session')->getFlashBag()->add('warning', 'No es posible acceder al archivo <b><i>"' . $archivoNombre . '"</i></b>. Verifique que este exista y tenga los permisos necesarios.');
            if ($parent == 'plugin')
                return $this->redirect($this->generateUrl('plugin_admin', array('id' => $id_parent)));
            else
                return $this->redirect($this->generateUrl('instancia_admin', array('id' => $id_parent)));
        } else {
            $DOM = new \DOMDocument('1.0', 'utf-8');
            $DOM->encoding = 'utf-8';
            $DOM->preserveWhiteSpace = false;
            $DOM->formatOutput = true;
            $DOM->loadXML($document->getContenido());
            $xpathf = new \DOMXPath($DOM);

            // Guardando eliminacion
            $valorAnterior = '<property>\r\n'
                    . '<name>' . $xpathf->query('/configuration/property[' . $no_property . ']/name')->item(0)->nodeValue . '</name>\r\n'
                    . '<value>' . $xpathf->query('/configuration/property[' . $no_property . ']/value')->item(0)->nodeValue . '</value>\r\n'
                    . '<description>' . $xpathf->query('/configuration/property[' . $no_property . ']/description')->item(0)->nodeValue . '</description>\r\n'
                    . '</property>';
            $modificacion = new modificacion(new \DateTime(), $xpathf->query('/configuration/property[' . $no_property . ']/name')->item(0)->nodeValue, '/configuration/property[' . $no_property . ']', $valorAnterior, '', 'ELIMINAR', 'FALSE', 'Se ha eliminado la propiedad ' . $xpathf->query('/configuration/property[' . $no_property . ']/name')->item(0)->nodeValue);

            $dm->persist($modificacion);
            $document->addModificacione($modificacion);

            //Almacenando nombre de la propiedad eliminada
            $nombrePropiedad = $xpathf->query('/configuration/property[' . $no_property . ']/name')->item(0)->nodeValue;

            // Eliminando propiedad
            $xpathf->query('/configuration')->item(0)->removeChild($xpathf->query('/configuration/property[' . $no_property . ']')->item(0));

            // Salvando datos en el sistema y en el archivo real
            $DOM->save($rutaFile);
            // Decodificando caracteres
            $newContent = html_entity_decode(file_get_contents($rutaFile), null, "UTF-8");
            // Guardando contenido decodificado
            $file = fopen($rutaFile, "w");
            fwrite($file, $newContent);
            fclose($file);

            $document->setContenido($newContent);
            // Persistiendo los cambios
            $dm->persist($document);
            $dm->flush();

            // Convierte el String del numero de la property en un Integer
            settype($no_property, 'int');

            // Bloque de actualizaciones
            $this->update_modificaciones_after_delete($parent, $id_parent, $no_property, $id);
            $this->update_etiquetas_after_delete($parent, $id_parent, $no_property, $id);
            $this->update_propiedades_perfil_after_delete($parent, $id_parent, $no_property, $id);
            $this->get('session')->getFlashBag()->add('success', 'Se ha eliminado la propiedad <i>"' . $nombrePropiedad . '"</i>');
            return $this->redirect($this->generateUrl('archivo_show', array('id' => $document->getId(), 'parent'
                                => $parent, 'id_parent' => $ $parent->getId())));
        }
    }

    /**
     * Elimina una propiedad (property) de un archivo de configuracion XML
     * 
     * @param integer $no_property Numero de la propiedad que se elimina  
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteGenericPropertyAction(Request $request, $parent, $id_parent, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('No se encontró el archivo relacionado en la base de datos.');
        }

        // Obteniendo la ruta del archivo
        $extension = '';
        $rutaFile = $document->getRuta() . '/' . $document->getNombre();
        if (\strtolower($document->getFormato()) != "null") {
            $extension = '.' . \strtolower($document->getFormato());
            $rutaFile .= $extension;
        }

        $archivoNombre = $document->getNombre() . $extension;

        if (!is_writable($rutaFile)) {
            $this->get('session')->getFlashBag()->add('warning', 'No es posible acceder al archivo <b><i>"' . $archivoNombre . '"</i></b>. Verifique que este exista y tenga los permisos necesarios.');
            if ($parent == 'plugin')
                return $this->redirect($this->generateUrl('plugin_admin', array('id' => $id_parent)));
            else
                return $this->redirect($this->generateUrl('instancia_admin', array('id' => $id_parent)));
        } else {
            $DOM = new \DOMDocument('1.0', 'utf-8');
            $DOM->encoding = 'utf-8';
            $DOM->preserveWhiteSpace = false;
            $DOM->formatOutput = true;
            $DOM->loadXML($document->getContenido());
            $xpathf = new \DOMXPath($DOM);

            $deleteGenericPropertyForm = $this->createForm(new archivoDeleteGenericPropertyType());
            $deleteGenericPropertyForm->bind($request);

            if ($deleteGenericPropertyForm->get('deleteGenericProperty')->getData() != '') {
                $rutaGenericProperty = $deleteGenericPropertyForm->get('deleteGenericProperty')->getData();

                // Almacenando nombre de la propiedad eliminada
                $nombrePropiedad = $xpathf->query($rutaGenericProperty)->item(0)->nodeName;

                // Guardando eliminacion
                $valorAnterior = $DOM->saveXML($xpathf->query($rutaGenericProperty)->item(0));
                $modificacion = new modificacion(new \DateTime(), $nombrePropiedad, $rutaGenericProperty, $valorAnterior, '', 'ELIMINAR', 'FALSE', 'Se ha eliminado la propiedad <i>"' . $nombrePropiedad . '"</i>');

                $dm->persist($modificacion);
                $document->addModificacione($modificacion);


                // Eliminando propiedad
                $xpathf->query($rutaGenericProperty)->item(0)->parentNode->removeChild($xpathf->query($rutaGenericProperty)->item(0));

                // Salvando datos en el sistema y en el archivo real
                $DOM->save($rutaFile);
                // Decodificando caracteres
                $newContent = html_entity_decode(file_get_contents($rutaFile), null, "UTF-8");
                // Guardando contenido decodificado
                $file = fopen($rutaFile, "w");
                fwrite($file, $newContent);
                fclose($file);

                $document->setContenido($newContent);
                // Persistiendo los cambios
                $dm->persist($document);
                $dm->flush();
                $this->get('session')->getFlashBag()->add('success', 'Se ha eliminado la propiedad <i>"' . $nombrePropiedad . '"</i>');
            } else {
                $this->get('session')->getFlashBag()->add('danger', 'No se pudo eliminar la propiedad del archivo');
            }
            return $this->redirect($this->generateUrl('archivo_show', array('id' => $document->getId(), 'parent'
                                => $parent, 'id_parent' => $ $parent->getId())));
        }
    }

    /**
     * Crea el formulario de eliminar Archivo
     * 
     * @param String $id ID del Archivo    
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

    /**
     * Funcion que actualiza la coleccion de modificaciones del archvo despues de eliminar una propiedad
     * 
     * @param integer $no_property Numero de la propiedad que se elimina  
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     */
    function update_modificaciones_after_delete($parent, $id_parent, $no_property, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        $modificaciones = $document->getModificaciones();

        foreach ($modificaciones as $modificacion) {
            $no_property_modificacion = $this->get_string_between($modificacion->getRutaPropiedad(), 'property[', ']');
            settype($no_property_modificacion, 'int');
            if ($modificacion->getTipoModificacion() != 'INSERTAR' && $no_property_modificacion > $no_property) {
                $newRutaPropiedad = preg_replace('/property\[\d+\]/', 'property[' . ($no_property_modificacion - 1) . ']', $modificacion->getRutaPropiedad());
                $modificacion->setRutaPropiedad($newRutaPropiedad);
            } elseif ($no_property_modificacion == $no_property) {
                $modificacion->setExistePropiedad('FALSE');
            }
        }

        // Persistiendo los cambios
        $dm->persist($document);
        $dm->flush();
    }

    /**
     * Funcion que actualiza la coleccion de etiquetas del archvo despues de eliminar una propiedad
     * 
     * @param integer $no_property Numero de la propiedad que se elimina  
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     */
    function update_etiquetas_after_delete($parent, $id_parent, $no_property, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        $etiquetas = $document->getEtiquetas();

        foreach ($etiquetas as $etiqueta) {
            $no_property_etiqueta = $this->get_string_between($etiqueta->getRutaPropiedad(), 'property[', ']');
            settype($no_property_etiqueta, 'int');
            if ($no_property_etiqueta > $no_property) {
                $newRutaPropiedad = preg_replace('/property\[\d+\]/', 'property[' . ($no_property_etiqueta - 1) . ']', $etiqueta->getRutaPropiedad());
                $etiqueta->setRutaPropiedad($newRutaPropiedad);
            } elseif ($no_property_etiqueta == $no_property) {
                $document->removeEtiqueta($etiqueta);
                $dm->remove($etiqueta);
            }
        }

        // Persistiendo los cambios
        $dm->persist($document);
        $dm->flush();
    }

    /**
     * Funcion utilizada para el autocompletado de la Ruta de un archivo
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request Datos de la solicitud
     * @return type
     */
    public function autocompletarAction(Request $request) {
        $directorio = $request->request->get('search');
        $type = $request->request->get('type');
        $sugerencias = array();

        if (preg_match('/^(\/.[^\/]+)+\/$/', $directorio) && is_dir($directorio)) {
            $dm = $this->getDocumentManager();
            $instancias_directorio = $dm->getRepository('ConfigOrionBundle:sistema')->findOneBy(array('nombre' => 'instancias_directorio'))->getValor();
            $exp_dir = '/^' . addcslashes($instancias_directorio, '/') . '/';
            if (preg_match($exp_dir, $directorio)) {
                $archivos = scandir($directorio);
                $cant_archivos = count($archivos);
                for ($i = 2; $i < $cant_archivos; $i++) {
                    $archivo = $directorio . $archivos[$i];
                    if ($type === 'directorio') {
                        if (is_dir($archivo))
                            $sugerencias[] = $archivo;
                    }
                    if ($type === 'archivo') {
                        if (is_file($archivo)) {
                            $fileName = $archivos[$i];
                            $endPos = strlen($fileName) - 1;
                            if ($fileName[$endPos] !== '~') {
                                $extension = strstr($fileName, '.');
                                $finfo = finfo_open(FILEINFO_MIME_TYPE); // Inicializando el recurso                             
                                $tipoMime = finfo_file($finfo, $archivo);  // Obtiene el tipo MIME del fichero          
                                finfo_close($finfo);   // Destruyendo el recurso                                
                                if ($extension !== FALSE) {
                                    $extension = strtolower($extension);
                                    if ($extension === '.xml' && $tipoMime === 'application/xml')
                                        $sugerencias[] = strstr($fileName, $extension, TRUE);
                                    if ($extension === '.txt' && $tipoMime === 'text/plain')
                                        $sugerencias[] = strstr($fileName, $extension, TRUE);
                                } elseif ($tipoMime === 'text/plain') {
                                    $sugerencias[] = $fileName;
                                }
                            }
                        }
                    }
                }
            }
        }
        return Response::create(json_encode($sugerencias));
    }

    /**
     * Funcion que actualiza la coleccion de propiedades de perfil del archvo despues de eliminar una propiedad
     * 
     * @param integer $no_property Numero de la propiedad que se elimina  
     * @param String $id ID del archivo
     * @param String $parent Toma el valor "instancia" o "plugin" 
     * @param String $id_parent ID de la instancia o plugin
     */
    function update_propiedades_perfil_after_delete($parent, $id_parent, $no_property, $id) {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $ $parent = $dm->getRepository('ConfigOrionBundle:' . $parent)->find($id_parent);

        $document = $dm->getRepository('ConfigOrionBundle:archivo')->find($id);

        $propiedadesPerfiles = $document->getPropiedadesPerfiles();

        foreach ($propiedadesPerfiles as $propiedad) {
            $no_property_etiqueta = $this->get_string_between($propiedad->getRutaPropiedad(), 'property[', ']');
            settype($no_property_etiqueta, 'int');
            if ($no_property_etiqueta > $no_property) {
                $newRutaPropiedad = preg_replace('/property\[\d+\]/', 'property[' . ($no_property_etiqueta - 1) . ']', $propiedad->getRutaPropiedad());
                $propiedad->setRutaPropiedad($newRutaPropiedad);
            } elseif ($no_property_etiqueta == $no_property) {
                $document->removePropiedadesPerfile($propiedad);
                $perfil = $dm->getRepository('ConfigOrionBundle:perfil')->getPerfilByPropiedadPerfil($propiedad);
                $perfil->removePropiedadesPerfile($propiedad);
                $dm->remove($propiedad);
                $dm->persist($perfil);
            }
        }

        // Persistiendo los cambios
        $dm->persist($document);
        $dm->flush();
    }

    /**
     * Funcion que devuelve el subString entre una cadena de inicio y una cadena de fin
     * 
     * @param string $string
     * @param type $start
     * @param type $end
     * @return string
     */
    private function get_string_between($string, $start, $end) {
        $string = " " . $string;
        $ini = strpos($string, $start);
        if ($ini == 0)
            return ""; $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

}

