<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller {

    /**
     * Controlador por defecto de Symfony2
     * 
     * @Route("/static/{staticPage}")
     * @Template()
     */
    public function staticPageAction($staticPage) {
        if ($staticPage == "inicio") {
            $usuario = $this->getUser();
            $nombre = $usuario->getUsername();
            return $this->render('ConfigOrionBundle:Static:' . $staticPage . '.html.twig', array('nombre' => $nombre));
        }
        return $this->render('ConfigOrionBundle:Static:' . $staticPage . '.html.twig');
    }

}
