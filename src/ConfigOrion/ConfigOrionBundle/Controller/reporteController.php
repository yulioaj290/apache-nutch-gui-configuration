<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ConfigOrion\ConfigOrionBundle\Form\reporteFilterType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ConfigOrion\ConfigOrionBundle\ExternalClases\InstanciaReport;

/**
 * Clase controladora que se encarga de la gestión de Reportes de Modificaciones
 */
class reporteController extends Controller {

    /**
     * Muestra la vista principal para la generación de Reportes de Modificaciones
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $choicelist = $this->getInstanciasList();
        $form = $this->createForm(new reporteFilterType($choicelist));
        // Obteniendo la fecha actual
        $fechaActual = date('d/m/Y');
        // Creando el intervalo de 1 Mes
        $interval = \DateInterval::createFromDateString('1 months');
        // Creando la fecha final
        $endFecha = \DateTime::createFromFormat('d/m/Y H:i:s', $fechaActual . ' 23:59:59');
        // Creando la fecha inicial
        $startFecha = \DateTime::createFromFormat('d/m/Y H:i:s', $fechaActual . ' 00:00:00');
        // Sustrayendo 1 Mes a la fecha final
        date_sub($startFecha, $interval);
        // Generando el reporte de modificaciones
        $reporte = $this->getReporte(NULL, $startFecha, $endFecha);
        // Creando el rango de fecha
        $periodo = $startFecha->format('d/m/Y') . ' - ' . $endFecha->format('d/m/Y');
        return $this->render('ConfigOrionBundle:reporte:filter.html.twig', array('reportes' => $reporte, 'reporteFilter' => $form->createView(),
                    'fecha' => $periodo, 'export' => FALSE));
    }

    /**
     * Muestra los resultados de un reporte luego de aplicar los filtros
     * 
     * @param Request $request Datos de la solicitud
     */
    public function filtrarAction(Request $request) {
        $choicelist = $this->getInstanciasList();
        $form = $this->createForm(new reporteFilterType($choicelist));
        $form->bind($request);
        if ($form->isValid()) {
            $fecha = $form->get('fecha')->getData();
            if (!empty($fecha)) {
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}\s\-\s\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
                    $id_instancia = $form->get('instancia')->getViewData();
                    // Obteniendo el rango de fecha
                    $fechaFormat = $this->splitFecha($fecha);
                    // Obteniendo todas las modificaciones que pertenecen al rango de fecha                
                    $export = FALSE;
                    $reporte = $this->getReporte($id_instancia, $fechaFormat[0], $fechaFormat[1]);
                    // Comprobando que existen modificaciones para mostrar el botón "Exportar"
                    if (!empty($reporte)) {
                        $export = TRUE;
                    } else {
                        $this->get('session')->getFlashBag()->add('warning', 'No existen registros de modificaciones para esta fecha.');
                        return $this->redirect($this->generateUrl('reporte'));
                    }
                    return $this->render('ConfigOrionBundle:reporte:filter.html.twig', array('reportes' => $reporte, 'reporteFilter' => $form->createView(),
                                'fecha' => $fecha, 'export' => $export));
                } else {
                    $this->get('session')->getFlashBag()->add('warning', 'El formato de la fecha es incorrecto. Debe introducir un rango de fecha válido.');
                }
            } else {
                $this->get('session')->getFlashBag()->add('warning', 'Para generar un reporte debe introducir un rango de fecha.');
            }
        }
        return $this->redirect($this->generateUrl('reporte'));
    }

    /**
     * Devuelve la fecha inicial y la fecha final dado un rango de fecha de la forma:
     * dd/mm/yyyy - dd/mm/yyyy
     * 
     * @param type $fecha Rango de fecha (dd/mm/yyyy - dd/mm/yyyy)
     * @return array Retorna en la primera posición del arreglo la fecha inicial 
     * y en la segunda posición la fecha final
     */
    private function splitFecha($fecha) {
        $splitFecha = explode(' - ', $fecha);
        $startFecha = \DateTime::createFromFormat('d/m/Y H:i:s', $splitFecha[0] . ' 00:00:00');
        $endFecha = \DateTime::createFromFormat('d/m/Y H:i:s', $splitFecha[1] . ' 24:00:00');
        return array($startFecha, $endFecha);
    }

    /**
     * Devuelve los resultados de un reporte al aplicar los filtros
     * 
     * @param String $id_instancia ID de la Instancia
     * @param String $startFecha Fecha inicial
     * @param String $endFecha Fecha final
     * @param boolean $wrap Divide un texto en varias líneas si la longitud
     * sobre pasa los 15 caracteres. Valor por defecto <b>FALSE</b>
     * @return array Retorna una lista de objetos de tipo <b>InstanciaReport</b>
     */
    private function getReporte($id_instancia = "", $startFecha, $endFecha, $wrap = FALSE) {
        $reporte = array();
        $dm = $this->getDocumentManager();
        if (empty($id_instancia)) {
            $instancias = $dm->getRepository('ConfigOrionBundle:instancia')->findAll();
        } else {
            $instancias = array($dm->getRepository('ConfigOrionBundle:instancia')->find($id_instancia));
        }
        foreach ($instancias as $instancia) {
            $archivos = $instancia->getArchivos();
            foreach ($archivos as $archivo) {
                $modificaciones = $archivo->getModificaciones();
                $item = new InstanciaReport($instancia->getNombre(), $wrap);
                $extension = strtolower($archivo->getFormato());
                if ($extension !== 'null')
                    $item->setArchivo($archivo->getNombre() . '.' . $extension);
                else
                    $item->setArchivo($archivo->getNombre());
                $item->setUbicacion($archivo->getRuta());
                foreach ($modificaciones as $modificacion) {
                    if ($modificacion->getFecha() >= $startFecha && $modificacion->getFecha() <= $endFecha) {
                        $itemRow = array($modificacion->getPropiedad(), $modificacion->getValorAnterior(),
                            $modificacion->getValorActual(), $modificacion->getFechaFormat());
                        $item->addModificacion($itemRow);
                    }
                }
                $is_modificaciones = $item->getModificaciones();
                if (!empty($is_modificaciones))
                    $reporte[] = $item;
            }
        }
        return $reporte;
    }

    /**
     * Exporta los resultados de un reporte a un documento PDF
     * 
     * @param Request $request Datos de la solicitud   
     */
    public function exportarAction(Request $request) {
        $choicelist = $this->getInstanciasList();
        $form = $this->createForm(new reporteFilterType($choicelist));
        $form->bind($request);
        if ($form->isValid()) {
            $fecha = $form->get('fecha')->getData();
            if (!empty($fecha)) {
                $id_instancia = $form->get('instancia')->getViewData();
                // Obteniendo el rango de fecha
                $fechaFormat = $this->splitFecha($fecha);
                $reporte = $this->getReporte($id_instancia, $fechaFormat[0], $fechaFormat[1], TRUE);
                $html = $this->renderView('ConfigOrionBundle:reporte:pdf.html.twig', array('reportes' => $reporte, 'fecha' => $fecha));
                $pdfGenerator = $this->get('spraed.pdf.generator');
                $pdf = $pdfGenerator->generatePDF($html);
                return new Response($pdf, 200, array(
                    'Content-Type' => 'application/force-download',
                    'Content-Transfer-Encoding' => 'binary',
                    'Content-length' => strlen($pdf),
                    'Content-Disposition' => 'attachment; filename="Informe de Comentarios.pdf"',
                    'Pragma' => 'no-cache',
                    'Expires' => '0')
                );
            }
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'Ha ocurrido un error inesperado mientras se generaba el reporte.');
        }
        return $this->redirect($this->generateUrl('reporte'));
    }

    /**
     * Crea una lista de selección con el nombre de las Instancias registradas 
     * en el sistema
     * 
     * @return array Lista de Instancias
     */
    private function getInstanciasList() {
        $dm = $this->getDocumentManager();
        $choicelist = array();
        $choicelist[''] = '[Seleccionar]';
        $instancias = $dm->getRepository('ConfigOrionBundle:instancia')->findAll();
        foreach ($instancias as $instancia) {
            $id = $instancia->getId();
            $choicelist[$id] = $instancia->getNombre();
        }
        return $choicelist;
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
