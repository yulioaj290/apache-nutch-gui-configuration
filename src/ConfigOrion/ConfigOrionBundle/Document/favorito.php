<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="favorito", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\favoritoRepository")
 */
class favorito {

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\String   
     */
    private $nombreArchivo;

    /**
     * @MongoDB\String  
     */
    private $rutaArchivo;

    /**
     * @MongoDB\String
     */
    private $formato;

    /**
     * @MongoDB\String
     */
    private $contenido;

    /**
     * @MongoDB\String    
     */
    private $nombreInstancia;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $descripcion;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nombreArchivo
     *
     * @param string $nombreArchivo
     * @return self
     */
    public function setNombreArchivo($nombreArchivo) {
        $this->nombreArchivo = $nombreArchivo;
        return $this;
    }

    /**
     * Get nombreArchivo
     *
     * @return string $nombreArchivo
     */
    public function getNombreArchivo() {
        return $this->nombreArchivo;
    }

    /**
     * Set rutaArchivo
     *
     * @param string $rutaArchivo
     * @return self
     */
    public function setRutaArchivo($rutaArchivo) {
        $this->rutaArchivo = $rutaArchivo;
        return $this;
    }

    /**
     * Get rutaArchivo
     *
     * @return string $rutaArchivo
     */
    public function getRutaArchivo() {
        return $this->rutaArchivo;
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
     * Set nombreInstancia
     *
     * @param string $nombreInstancia
     * @return self
     */
    public function setNombreInstancia($nombreInstancia) {
        $this->nombreInstancia = $nombreInstancia;
        return $this;
    }

    /**
     * Get nombreInstancia
     *
     * @return string $nombreInstancia
     */
    public function getNombreInstancia() {
        return $this->nombreInstancia;
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

}
