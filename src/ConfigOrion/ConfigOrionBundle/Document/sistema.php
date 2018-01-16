<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="sistema", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\sistemaRepository")
 */
class sistema {

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $nombre;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $valor;
    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $descripcion;


    /**
     * Get id
     *
     * @return int_id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nombre
     *
     * @param string $nombre
     * @return self
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
        return $this;
    }

    /**
     * Get nombre
     *
     * @return string $nombre
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /**
     * Set valor
     *
     * @param string $valor
     * @return self
     */
    public function setValor($valor)
    {
        $this->valor = $valor;
        return $this;
    }

    /**
     * Get valor
     *
     * @return string $valor
     */
    public function getValor()
    {
        return $this->valor;
    }

    /**
     * Set descripcion
     *
     * @param string $descripcion
     * @return self
     */
    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    /**
     * Get descripcion
     *
     * @return string $descripcion
     */
    public function getDescripcion()
    {
        return $this->descripcion;
    }
}
