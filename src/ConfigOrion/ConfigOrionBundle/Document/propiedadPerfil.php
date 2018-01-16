<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="propiedadPerfil", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\propiedadPerfilRepository")
 */
class propiedadPerfil {

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $propiedad;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $rutaPropiedad;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="El campo Valor es obligatorio")
     */
    private $valor;

    /**
     * @MongoDB\ReferenceOne(targetDocument="archivo")
     */
    private $ArchivoId;

    /**
     * Get id
     *
     * @return int_id $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set propiedad
     *
     * @param string $propiedad
     * @return self
     */
    public function setPropiedad($propiedad) {
        $this->propiedad = $propiedad;
        return $this;
    }

    /**
     * Get propiedad
     *
     * @return string $propiedad
     */
    public function getPropiedad() {
        return $this->propiedad;
    }

    /**
     * Set rutaPropiedad
     *
     * @param string $rutaPropiedad
     * @return self
     */
    public function setRutaPropiedad($rutaPropiedad) {
        $this->rutaPropiedad = $rutaPropiedad;
        return $this;
    }

    /**
     * Get rutaPropiedad
     *
     * @return string $rutaPropiedad
     */
    public function getRutaPropiedad() {
        return $this->rutaPropiedad;
    }

    /**
     * Set valor
     *
     * @param string $valor
     * @return self
     */
    public function setValor($valor) {
        $this->valor = $valor;
        return $this;
    }

    /**
     * Get valor
     *
     * @return string $valor
     */
    public function getValor() {
        return $this->valor;
    }


    /**
     * Set archivoId
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\archivo $archivoId
     * @return self
     */
    public function setArchivoId(\ConfigOrion\ConfigOrionBundle\Document\archivo $archivoId)
    {
        $this->ArchivoId = $archivoId;
        return $this;
    }

    /**
     * Get archivoId
     *
     * @return ConfigOrion\ConfigOrionBundle\Document\archivo $archivoId
     */
    public function getArchivoId()
    {
        return $this->ArchivoId;
    }
}
