<?php

namespace ConfigOrion\ConfigOrionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Clase Repositorio del Documento "sistema" 
 */
class sistemaRepository extends DocumentRepository {

    /**
     * Obtiene una Propiedad del Sistema a partir de su nombre
     * 
     * @param String $name Nombre de la Propiedad del Sistema
     * @return type Retorna un objeto de tipo Document "sistema" 
     */
    public function findPropertyByName($name) {
        return $this->createQueryBuilder()
                        ->field('nombre')->equals($name)
                        ->getQuery()
                        ->execute();
    }

}
