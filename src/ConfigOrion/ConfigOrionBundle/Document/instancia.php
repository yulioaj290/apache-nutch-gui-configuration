<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="instancia", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\instanciaRepository")
 */
class instancia {

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
    private $ruta;

    /**
     * @MongoDB\String
     */
    private $descripcion;

    /**
     * @MongoDB\ReferenceMany(targetDocument="archivo", cascade="all")
     */
    private $archivos = array();

    /**
     * @MongoDB\ReferenceMany(targetDocument="plugin", cascade="all")
     */
    private $plugins = array();

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
     * Set ruta
     *
     * @param string $ruta
     * @return self
     */
    public function setRuta($ruta) {
        $this->ruta = $ruta;
        return $this;
    }

    /**
     * Get ruta
     *
     * @return string $ruta
     */
    public function getRuta() {
        return $this->ruta;
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
        $this->archivos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->plugins = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add archivo
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\archivo $archivo
     */
    public function addArchivo(\ConfigOrion\ConfigOrionBundle\Document\archivo $archivo) {
        $this->archivos[] = $archivo;
    }

    /**
     * Remove archivo
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\archivo $archivo
     */
    public function removeArchivo(\ConfigOrion\ConfigOrionBundle\Document\archivo $archivo) {
        $this->archivos->removeElement($archivo);
    }

    /**
     * Get archivos
     *
     * @return Doctrine\Common\Collections\Collection $archivos
     */
    public function getArchivos() {
        return $this->archivos;
    }

    /**
     * Add plugin
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\plugin $plugin
     */
    public function addPlugin(\ConfigOrion\ConfigOrionBundle\Document\plugin $plugin) {
        $this->plugins[] = $plugin;
    }

    /**
     * Remove plugin
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\plugin $plugin
     */
    public function removePlugin(\ConfigOrion\ConfigOrionBundle\Document\plugin $plugin) {
        $this->plugins->removeElement($plugin);
    }

    /**
     * Get plugins
     *
     * @return Doctrine\Common\Collections\Collection $plugins
     */
    public function getPlugins() {
        return $this->plugins;
    }

    public function __toString() {
        return $this->nombre;
    }

}
