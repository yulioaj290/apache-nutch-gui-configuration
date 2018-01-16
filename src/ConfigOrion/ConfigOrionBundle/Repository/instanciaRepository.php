<?php

namespace ConfigOrion\ConfigOrionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Clase Repositorio del Documento "instancia" 
 */
class instanciaRepository extends DocumentRepository {

    /**
     * Obtiene el Document de tipo Instancia asociado a un Archivo
     * 
     * @param Document $archivo Document de tipo "archivo" 
     * @return Document Retorna un objeto de tipo Document Archivo 
     */
    public function getInstanciaByArchivo($archivo) {
        return $this->createQueryBuilder()
                        ->field('archivos')->references($archivo)
                        ->getQuery()->getSingleResult();
    }

    /**
     * Obtiene el Document de tipo Instancia asociado a un Plugin
     * 
     * @param Document $archivo Document de tipo "plugin" 
     * @return Document Retorna un objeto de tipo Document Archivo 
     */
    public function getInstanciaByPlugin($plugin) {
        return $this->createQueryBuilder()
                        ->field('plugins')->references($plugin)
                        ->getQuery()->getSingleResult();
    }

}
