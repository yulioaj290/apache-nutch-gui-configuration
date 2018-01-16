<?php

namespace ConfigOrion\ConfigOrionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Clase Repositorio del Documento "archivo" 
 */
class archivoRepository extends DocumentRepository {

    /**
     * Obtiene el Document de tipo Archivo asociado a una Propiedad de Perfil
     * 
     * @param Document $propiedad Document de tipo propiedadPerfil
     * @return Document Retorna un objeto de tipo Document Archivo
     */
    public function getArchivoByPropiedadPerfil($propiedad) {
        return $this->createQueryBuilder()
                        ->field('propiedadesPerfiles')->references($propiedad)
                        ->getQuery()->getSingleResult();
    }

    /**
     * Obtiene el Document de tipo Archivo asociado a una Etiqueta
     * 
     * @param Document $etiqueta Document de tipo Etiqueta
     * @return Document Retorna un objeto de tipo Document Archivo
     */
    public function getArchivoByEtiqueta($etiqueta) {
        return $this->createQueryBuilder()
                        ->field('etiquetas')->references($etiqueta)
                        ->getQuery()->getSingleResult();
    }

}
