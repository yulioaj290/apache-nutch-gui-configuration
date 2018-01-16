<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Form\graficoFilterType;
use ConfigOrion\ConfigOrionBundle\ExternalClases\GenerarGraficos;

/**
 * Clase controladora que se encarga de generar gráficos estadísticos de modificaciones
 */
class graficosController extends Controller {

    /**
     * Muestra la vista por defecto de los gráficos estadísticos de modificaciones
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $dm = $this->getDocumentManager();
        $form = $this->createForm(new graficoFilterType($this->choiceList()));

        $arrayDataMods = GenerarGraficos::cantidadModificaciones($dm);

        $array_mods_archivo = GenerarGraficos::modificacionesPorArchivo($dm);

        return $this->render('ConfigOrionBundle:graficos:index.html.twig', array(
                    'form' => $form->createView(),
                    'array_data_mods' => $arrayDataMods,
                    'array_data_mods_tipo' => GenerarGraficos::modificacionesPorTipo($dm),
                    'array_mods_archivo_asc' => $array_mods_archivo[0],
                    'array_mods_archivo_des' => $array_mods_archivo[1],
        ));
    }

    /**
     * Muestra los gráficos estadísticos de modificaciones con los con los datos de los filtros seleccionados
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterAction(Request $request) {
        $dm = $this->getDocumentManager();
        $form = $this->createForm(new graficoFilterType($this->choiceList()));
        $form->bind($request);

        if ($form->isValid()) {
            if ($form->get('cantidad')->getData() == '' && $form->get('archivo')->getData() == '' && $form->get('fecha')->getData() == '') {
                return $this->redirect($this->generateUrl('graficos'));
            } elseif ($form->get('cantidad')->getData() != '' && !is_numeric($form->get('cantidad')->getData())) {
                $this->get('session')->getFlashBag()->add('warning', 'El filtro "Últimas modificaciones" debe contener un valor numérico.');
            } elseif ($form->get('fecha')->getData() != '' && !preg_match('/^\d{2}\/\d{2}\/\d{4}\s\-\s\d{2}\/\d{2}\/\d{4}$/', $form->get('fecha')->getData())) {
                $this->get('session')->getFlashBag()->add('warning', 'El formato de la fecha es incorrecto. Debe introducir un rango de fecha válido.');
            } else {
                // Asignando valores de los filtros
                $NoUltimos = $form->get('cantidad')->getData();
                $archivo = $form->get('archivo')->getData();
                $fecha = $form->get('fecha')->getData();

                $arrayDataMods = GenerarGraficos::cantidadModificaciones($dm, $NoUltimos, $archivo, $fecha);

                $array_mods_archivo = GenerarGraficos::modificacionesPorArchivo($dm, $NoUltimos, $archivo, $fecha);

                $dataFilterArchivo = explode('|', $archivo);

                return $this->render('ConfigOrionBundle:graficos:index.html.twig', array(
                            'form' => $form->createView(),
                            'array_data_mods' => $arrayDataMods,
                            'array_data_mods_tipo' => GenerarGraficos::modificacionesPorTipo($dm, $NoUltimos, $archivo, $fecha),
                            'array_mods_archivo_asc' => $array_mods_archivo[0],
                            'array_mods_archivo_des' => $array_mods_archivo[1],
                            'data_filter_archivo' => $dataFilterArchivo[0],
                ));
            }
        }
        return $this->redirect($this->generateUrl('graficos'));
    }

    /**
     * Muestra el gráfico estadístico Spline de modificaciones con los con los datos de los filtros seleccionados
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterSplineAction(Request $request) {
        $dm = $this->getDocumentManager();
        $form = $this->createForm(new graficoFilterType($this->choiceList()));
        $form->bind($request);

        if ($form->isValid()) {
            if($form->get('fecha')->getData() == '') {
                return $this->redirect($this->generateUrl('graficos_spline'));
            } elseif ($form->get('fecha')->getData() != '' &&!preg_match('/^\d{2}\/\d{2}\/\d{4}\s\-\s\d{2}\/\d{2}\/\d{4}$/', $form->get('fecha')->getData())) {
                $this->get('session')->getFlashBag()->add('warning', 'El formato de la fecha es incorrecto. Debe introducir un rango de fecha válido.');
            } else {
                // Asignando valores de los filtros
                $fecha = $form->get('fecha')->getData();

                $arrayDataMods = GenerarGraficos::cantidadModificacionesHistoricas($dm, $fecha);

                return $this->render('ConfigOrionBundle:graficos:spline.html.twig', array(
                            'form' => $form->createView(),
                            'array_data_mods' => $arrayDataMods,
                ));
            }
        }

        return $this->redirect($this->generateUrl('graficos_spline'));
    }

    /**
     * Muestra la vista del gráfico Spline
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function splineAction() {
        $dm = $this->getDocumentManager();
        $form = $this->createForm(new graficoFilterType($this->choiceList()));

        $arrayDataMods = GenerarGraficos::cantidadModificacionesHistoricas($dm);
        return $this->render('ConfigOrionBundle:graficos:spline.html.twig', array(
                    'form' => $form->createView(),
                    'array_data_mods' => $arrayDataMods,
        ));
    }

    /**
     * Crea una lista de selección basada en la jerarquía (Instancia - Plugin - Archivo)
     * 
     * @return array Lista de Instancias, Plugins y Archivos
     */
    public function choiceList() {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();
        $instancias = $dm->getRepository('ConfigOrionBundle:instancia')->findAll();
        $list = array();
        $list[''] = '[Seleccionar]';
        foreach ($instancias as $instancia) {
            $list['instancia|' . $instancia->getId()] = '[I] ' . $instancia->getNombre();
            $plugins = $instancia->getPlugins();
            foreach ($plugins as $plugin) {
                $list['plugin|' . $plugin->getId()] = '** [P] ' . $plugin->getNombre();
                $archivosP = $plugin->getArchivos();
                foreach ($archivosP as $ArchivoP) {
                    if ($ArchivoP->getFormato() == 'NULL') {
                        $list['archivo|' . $ArchivoP->getId()] = '------ [A] ' . $ArchivoP->getNombre();
                    } else {
                        $list['archivo|' . $ArchivoP->getId()] = '------ [A] ' . $ArchivoP->getNombre() . '.' . strtolower($ArchivoP->getFormato());
                    }
                }
            }
            $archivos = $instancia->getArchivos();
            foreach ($archivos as $Archivo) {
                if ($Archivo->getFormato() == 'NULL') {
                    $list['archivo|' . $Archivo->getId()] = '-- [A] ' . $Archivo->getNombre();
                } else {
                    $list['archivo|' . $Archivo->getId()] = '-- [A] ' . $Archivo->getNombre() . '.' . strtolower($Archivo->getFormato());
                }
            }
        }
        return $list;
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

