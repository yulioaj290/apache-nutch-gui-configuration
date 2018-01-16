<?php

namespace ConfigOrion\ConfigOrionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Clase Repositorio del Documento "modificacion" 
 */
class modificacionRepository extends DocumentRepository {

    /**
     * Obtiene todos los Documents de tipo Modificacion ordenados descendentemente por 
     * el campo fecha
     * 
     * @return array Lista de Documents de tipo Modificacion
     */
    public function findByFechaSortDES() {
        $modificaciones = $this->createQueryBuilder('ConfigOrionBundle:modificacion')
                ->sort('fecha', 'DES')
                ->getQuery()
                ->execute();
        return $modificaciones;
    }

    /**
     * Obtiene todos los Documents de tipo Modificacion ordenados ascendentemente por 
     * el campo fecha
     * 
     * @return array Lista de Documents de tipo Modificacion
     */
    public function findByFechaSortASC() {
        $modificaciones = $this->createQueryBuilder('ConfigOrionBundle:modificacion')
                ->sort('fecha', 'ASC')
                ->getQuery()
                ->execute();
        return $modificaciones;
    }

}
