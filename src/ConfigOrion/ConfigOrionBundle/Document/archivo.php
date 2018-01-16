<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="archivo", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\archivoRepository")
 */
class archivo {

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
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $formato;

    /**
     * @MongoDB\String
     */
    private $contenido;

    /**
     * @MongoDB\String
     */
    private $descripcion;

    /**
     * @MongoDB\ReferenceMany(targetDocument="propiedadPerfil", cascade="all")
     */
    private $propiedadesPerfiles = array();

    /**
     * @MongoDB\ReferenceMany(targetDocument="etiqueta", cascade="all")
     */
    private $etiquetas = array();

    /**
     * @MongoDB\ReferenceMany(targetDocument="modificacion", cascade="all")
     */
    private $modificaciones = array();

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
     * Set formato
     *
     * @param string $formato
     * @return self
     */
    public function setFormato($formato) {
        $this->formato = $formato;
        return $this;
    }

    /**
     * Get formato
     *
     * @return string $formato
     */
    public function getFormato() {
        return $this->formato;
    }

    /**
     * Set contenido
     *
     * @param string $contenido
     * @return self
     */
    public function setContenido($contenido) {
        $this->contenido = $contenido;
        return $this;
    }

    /**
     * Get contenido
     *
     * @return string $contenido
     */
    public function getContenido() {
        return $this->contenido;
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
        $this->etiquetas = new \Doctrine\Common\Collections\ArrayCollection();
        $this->modificaciones = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add etiqueta
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\etiqueta $etiqueta
     */
    public function addEtiqueta(\ConfigOrion\ConfigOrionBundle\Document\etiqueta $etiqueta) {
        $this->etiquetas[] = $etiqueta;
    }

    /**
     * Remove etiqueta
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\etiqueta $etiqueta
     */
    public function removeEtiqueta(\ConfigOrion\ConfigOrionBundle\Document\etiqueta $etiqueta) {
        $this->etiquetas->removeElement($etiqueta);
    }

    /**
     * Get etiquetas
     *
     * @return Doctrine\Common\Collections\Collection $etiquetas
     */
    public function getEtiquetas() {
        return $this->etiquetas;
    }

    /**
     * Add modificacione
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\modificacion $modificacione
     */
    public function addModificacione(\ConfigOrion\ConfigOrionBundle\Document\modificacion $modificacione) {
        $this->modificaciones[] = $modificacione;
    }

    /**
     * Remove modificacione
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\modificacion $modificacione
     */
    public function removeModificacione(\ConfigOrion\ConfigOrionBundle\Document\modificacion $modificacione) {
        $this->modificaciones->removeElement($modificacione);
    }

    /**
     * Get modificaciones
     *
     * @return Doctrine\Common\Collections\Collection $modificaciones
     */
    public function getModificaciones() {
        return $this->modificaciones;
    }


    /**
     * Add propiedadesPerfile
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile
     */
    public function addPropiedadesPerfile(\ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile)
    {
        $this->propiedadesPerfiles[] = $propiedadesPerfile;
    }

    /**
     * Remove propiedadesPerfile
     *
     * @param ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile
     */
    public function removePropiedadesPerfile(\ConfigOrion\ConfigOrionBundle\Document\propiedadPerfil $propiedadesPerfile)
    {
        $this->propiedadesPerfiles->removeElement($propiedadesPerfile);
    }

    /**
     * Get propiedadesPerfiles
     *
     * @return Doctrine\Common\Collections\Collection $propiedadesPerfiles
     */
    public function getPropiedadesPerfiles()
    {
        return $this->propiedadesPerfiles;
    }
}
