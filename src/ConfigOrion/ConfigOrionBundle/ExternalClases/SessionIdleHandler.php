<?php

namespace ConfigOrion\ConfigOrionBundle\ExternalClases;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContext;

/******************************************************************************
  services
  parameters:
      session_idle.options:
          idleTime: 2
          redirectRoute: usuario_login
  
  services:
      handler.session_idle:
          class: ConfigOrion\ConfigOrionBundle\ExternalClases\SessionIdleHandler
          arguments: [ @router, @security.context, "%session_idle.options%" ]
          tags:
              - { name: kernel.event_listener, event: kernel.request, method: onKernelRequest }
 
******************************************************************************/

/**
 * La clase <b>SessionIdleHandler</b> se utiliza para expirar la sesión del usuario 
 * cuando pase un tiempo de inactividad en el sistema como medida de seguridad
 */
class SessionIdleHandler {

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var array
     */
    protected $options;

    /**
     * Crea una instancia de la clase SessionIdleHandler
     *
     * @param   RouterInterface $router
     * @param   SecurityContext $context
     * @param   array           $options
     */
    public function __construct(RouterInterface $router, SecurityContext $context, $options = array()) {
        $this->router = $router;
        $this->securityContext = $context;
        $this->options = $options;
    }

    /**
     * Manejador de eventos de tipo Request
     *
     * @param   GetResponseEvent    $event
     * @return  void
     */
    public function onKernelRequest(GetResponseEvent $event) {
        // Only act on authenticated requests
        if (!$this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        // Get session
        $session = $event->getRequest()->getSession();

        // Set default for last action (now) if not set
        if (!$session->has('lastaction')) {
            $session->set('lastaction', time());
        }

        if ((time() - $session->get('lastaction')) < ($this->options['idleTime'] * 60)) {

            $session->set('lastaction', time());
        } else {

            // Log the current user out
            $session->invalidate();
            $this->securityContext->setToken(null);

            // Set flash message
            $session->setFlash('message', 'Usted ha sido desautenticado automáticamente por el sistema.');

            // Generate redirect response and set as new response
            // on the original event
            $url = $this->router->generate($this->options['redirectRoute']);
            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }
    }

}
