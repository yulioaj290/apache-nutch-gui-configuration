<?php

namespace ConfigOrion\ConfigOrionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Clase Repositorio del Documento "perfil" 
 */
class perfilRepository extends DocumentRepository {

    /**
     * Obtiene el Document de tipo Perfil asociado a una Propiedad de Perfil
     * 
     * @param Document $propiedad Document de tipo "propiedadPerfil" 
     * @return Document Retorna un objeto de tipo Document "perfil" 
     */
    public function getPerfilByPropiedadPerfil($propiedad) {
        return $this->createQueryBuilder()
                        ->field('propiedadesPerfiles')->references($propiedad)
                        ->getQuery()->getSingleResult();
    }

}
