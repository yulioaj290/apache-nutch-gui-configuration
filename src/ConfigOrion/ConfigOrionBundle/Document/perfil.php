<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="perfil", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\perfilRepository")
 */
class perfil {

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
    private $descripcion;

    /**
     * @MongoDB\ReferenceMany(targetDocument="propiedadPerfil", cascade="all")
     */
    private $propiedadesPerfiles = array();

    /**
     * Get id
     *
     * @return int_id $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nombre
     *
     * @param string $nombre
     * @return self
     */
    public function setNombre($nombre) {
        $this->nombre = $nombre;
        return $this;
    }

    /**
     * Get nombre
     *
     * @return string $nombre
     */
    public function getNombre() {
        return $this->nombre;
    }

    /**
     * Set descripcion
     *
     * @param string $descripcion
     * @return self
     */
    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;
        return $this;
    }

    /**
     * Get descripcion
     *
     * @return string $descripcion
     */
    public function getDescripcion() {
        return $this->descripcion;
    }

    public function __construct() {
        $this->propiedadesPerfiles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add propiedadesPerfile
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile
     */
    public function addPropiedadesPerfile(\ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile) {
        $this->propiedadesPerfiles[] = $propiedadesPerfile;
    }

    /**
     * Remove propiedadesPerfile
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile
     */
    public function removePropiedadesPerfile(\ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile) {
        $this->propiedadesPerfiles->removeElement($propiedadesPerfile);
    }

    /**
     * Get propiedadesPerfiles
     *
     * @return Doctrine\Common\Collections\Collection $propiedadesPerfiles
     */
    public function getPropiedadesPerfiles() {
        return $this->propiedadesPerfiles;
    }

    public function __toString() {
        return $this->nombre;
    }

}
