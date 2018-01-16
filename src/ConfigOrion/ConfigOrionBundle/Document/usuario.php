<?php

namespace ConfigOrion\ConfigOrionBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique;

/**
 * @MongoDB\Document(collection="usuario", repositoryClass="ConfigOrion\ConfigOrionBundle\Repository\usuarioRepository")
 * @Unique(fields="username", message="Ya existe un usuario con ese nombre")
 * @Unique(fields="email", message="Ya existe un usuario con ese E-Mail")
 */
class usuario implements UserInterface {

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    protected $nombre;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    protected $username;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     */
    protected $roles;

    /**
     * @MongoDB\String
     */
    protected $password;

    /**
     * @MongoDB\String
     * @Assert\NotBlank(message="Este campo es obligatorio")
     * @Assert\Email(message="Este valor no es una dirección de correo válida")
     */
    protected $email;
    protected $salt;

    function __construct($id, $nombre, $username, $rol, $password, $email, $salt) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->username = $username;
        $this->roles = $rol;
        $this->password = $password;
        $this->email = $email;
        $this->salt = $salt;
    }

    
    public function eraseCredentials() {
        
    }

    public function getPassword() {
        return $this->password;
    }

    public function getRoles() {
        return array($this->roles);
    }

    public function getSalt() {
        return $this->salt;
    }

    public function getUsername() {
        return $this->username;
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
     * Set username
     *
     * @param string $username
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param String
     */
    public function setRol($role)
    {
        $this->roles = $role;
    }
    
  
    public function setRoles(array $roles)
    {
        $this->roles = $roles[0];
    }

    /**
     * Set password
     *
     * @param string $password
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }
}
