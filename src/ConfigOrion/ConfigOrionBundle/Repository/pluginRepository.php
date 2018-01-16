<?php

namespace ConfigOrion\ConfigOrionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Clase Repositorio del Documento "plugin" 
 */
class pluginRepository extends DocumentRepository {

    /**
     * Obtiene el Document de tipo Plugin asociado a un Archivo
     * 
     * @param Document $propiedad Document de tipo "archivo" 
     * @return Document Retorna un objeto de tipo Document "plugin" 
     */
    public function getPluginByArchivo($archivo) {
        return $this->createQueryBuilder()
                        ->field('archivos')->references($archivo)
                        ->getQuery()->getSingleResult();
    }

}
