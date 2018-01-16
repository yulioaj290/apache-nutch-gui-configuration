<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="modificacion", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\modificacionRepository")
 */
class modificacion {
     /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Date
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    private $fecha;
    
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
    */
    private $valorAnterior;
    
    /**
     * @MongoDB\String
    */
    private $valorActual;
    
    /**
     * Posibles valores ("INSERTAR", "ELIMINAR", "MODIFICAR")
     * @MongoDB\String
    */
    private $tipoModificacion;
    
    /**
     * Posibles valores ("TRUE", "FALSE")
     * @MongoDB\String
    */
    private $existePropiedad;
    
    /**
     * @MongoDB\String
    */
    private $descripcion;

    function __construct($fecha, $propiedad, $rutaPropiedad, $valorAnterior, $valorActual, $tipoModificacion, $existePropiedad, $descripcion) {
        $this->fecha = $fecha;
        $this->propiedad = $propiedad;
        $this->rutaPropiedad = $rutaPropiedad;
        $this->valorAnterior = $valorAnterior;
        $this->valorActual = $valorActual;
        $this->tipoModificacion = $tipoModificacion;
        $this->existePropiedad = $existePropiedad;
        $this->descripcion = $descripcion;
    }

    
    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set fecha
     *
     * @param date $fecha
     * @return self
     */
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
        return $this;
    }

    /**
     * Get fecha
     *
     * @return date $fecha
     */
    public function getFecha()
    {
        return $this->fecha;
    }

    /**
     * Get fecha formatted
     *
     * @return date $fecha
     */
    public function getFechaFormat()
    {
        return $this->fecha->format('d/m/Y');
    } 

    /**
     * Set propiedad
     *
     * @param string $propiedad
     * @return self
     */
    public function setPropiedad($propiedad)
    {
        $this->propiedad = $propiedad;
        return $this;
    }

    /**
     * Get propiedad
     *
     * @return string $propiedad
     */
    public function getPropiedad()
    {
        return $this->propiedad;
    }

    /**
     * Set rutaPropiedad
     *
     * @param string $rutaPropiedad
     * @return self
     */
    public function setRutaPropiedad($rutaPropiedad)
    {
        $this->rutaPropiedad = $rutaPropiedad;
        return $this;
    }

    /**
     * Get rutaPropiedad
     *
     * @return string $rutaPropiedad
     */
    public function getRutaPropiedad()
    {
        return $this->rutaPropiedad;
    }

    /**
     * Set valorAnterior
     *
     * @param string $valorAnterior
     * @return self
     */
    public function setValorAnterior($valorAnterior)
    {
        $this->valorAnterior = $valorAnterior;
        return $this;
    }

    /**
     * Get valorAnterior
     *
     * @return string $valorAnterior
     */
    public function getValorAnterior()
    {
        return $this->valorAnterior;
    }

    /**
     * Set valorActual
     *
     * @param string $valorActual
     * @return self
     */
    public function setValorActual($valorActual)
    {
        $this->valorActual = $valorActual;
        return $this;
    }

    /**
     * Get valorActual
     *
     * @return string $valorActual
     */
    public function getValorActual()
    {
        return $this->valorActual;
    }

    /**
     * Set tipoModificacion
     * Posibles valores ("INSERTAR", "ELIMINAR", "MODIFICAR")
     * @param string $tipoModificacion
     * @return self
     */
    public function setTipoModificacion($tipoModificacion)
    {
        $this->tipoModificacion = $tipoModificacion;
        return $this;
    }

    /**
     * Get tipoModificacion
     * Posibles valores ("INSERTAR", "ELIMINAR", "MODIFICAR")
     * @return string $tipoModificacion
     */
    public function getTipoModificacion()
    {
        return $this->tipoModificacion;
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

    /**
     * Set existePropiedad
     *
     * @param string $existePropiedad
     * @return self
     */
    public function setExistePropiedad($existePropiedad)
    {
        $this->existePropiedad = $existePropiedad;
        return $this;
    }

    /**
     * Get existePropiedad
     *
     * @return string $existePropiedad
     */
    public function getExistePropiedad()
    {
        return $this->existePropiedad;
    }
}
