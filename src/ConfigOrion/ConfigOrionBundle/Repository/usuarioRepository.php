<?php

namespace ConfigOrion\ConfigOrionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Clase Repositorio de la clase Document Usuario
 * 
 * Elaborada segun la documentacion oficial de Symfony2
 */
class usuarioRepository extends DocumentRepository implements UserProviderInterface {

    public function loadUserByUsername($username) {

        $users = $this->getDocumentManager()
                ->createQueryBuilder('ConfigOrionBundle:usuario')
                ->field('username')
                ->equals((string) $username)
                ->getQuery()
                ->execute();
        /**
         * Este bloque de try - catch viene por defecto en la documentacion de Symfony2, 
         * no se usa directamente en ningun controlador, sino que lo usa Symfony2 inernamente
         */
        try { 
            $user = $users->getSingleResult();
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException(sprintf('El usuario "%s" no ha sido encontrado.', $username), null, 0, $e);
        }

        return $user;
    }

    public function refreshUser(\Symfony\Component\Security\Core\User\UserInterface $user) {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('El usuario "%s" no ha sido encontrado.', $class));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class) {
        return $class === 'ConfigOrion\ConfigOrionBundle\Document\usuario';
    }

}
