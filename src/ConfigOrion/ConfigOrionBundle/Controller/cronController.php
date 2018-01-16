<?php

namespace ConfigOrion\ConfigOrionBundle\Controller;

use \Symfony\Component\Serializer\Encoder\JsonEncoder;
use \Symfony\Component\Serializer\Serializer;
use \Symfony\Component\HttpFoundation\Response;
use \ConfigOrion\ConfigOrionBundle\Form\CronType;
use \ConfigOrion\ConfigOrionBundle\ExternalClases\Cron;
use \ConfigOrion\ConfigOrionBundle\ExternalClases\CronManager;
use \Symfony\Bundle\FrameworkBundle\Controller\Controller;

class cronController extends Controller
{

    /**
     * Displays the current crons and a form to add a new one.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function indexAction()
    {
        $cm = new CronManager();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());

        $form = $this->createForm(new CronType(), new Cron());

        return $this->render('ConfigOrionBundle:cron/Default:index.html.twig', array(
            'crons' => $this->getCrons($cm),
            'raw' => $cm->getRaw(),
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays the current crons of an instanceand a form to add a new one.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function adminAction($id)
    {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id);

        if (!$instancia) {
            throw $this->createNotFoundException('La instancia seleccionada ya no se encuentra disponible en la base de datos.');
        }

        $cm = new CronManager();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());

        $form = $this->createForm(new CronType(), new Cron());

        return $this->render('ConfigOrionBundle:cron/Default:index.html.twig', array(
            'crons' => $this->getCronsByInstance($id),
            'raw' => $cm->getRaw(),
            'form' => $form->createView(),
            'instancia' => $instancia,
        ));
    }

    /**
     * Gets the array of crons filtered by instance
     *
     * @return array<Cron>
     */
    public function getCronsByInstance($id)
    {
        $cm = new CronManager();
        $lines = $cm->get();
        $new_cm = array();
        foreach ($lines as $i => &$value) {
            if ($value instanceof Cron) {
                if (\strpos($value->getComment(), '-instance_id:') !== FALSE &&
                    \substr($value->getComment(), -24, 24) == $id
                ) {
                    $value->setComment(substr_replace($value->getComment(), "", -37));
                    $new_cm[$i] = $value;
                }
            }
        }
        return $new_cm;
    }

    /**
     * Gets the array of crons indexed by line number
     *
     * @return array<Cron>
     */
    public function getCrons($cm)
    {
        return \array_filter($cm->get(), function ($line) {
            if ($line instanceof Cron) {
                if (\strpos($line->getComment(), '-instance_id:') !== FALSE) {
                    $line->setComment(substr_replace($line->getComment(), "", -37));
                    return $line;
                } else {
                    return $line;
                }
            }
        });
    }


    /**
     * Add a cron to the cron table
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function addAction()
    {
        $cm = new CronManager();
        $cron = new Cron();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());
        $form = $this->createForm(new CronType(), $cron);

        $request = $this->get('request');
        if ('POST' == $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $instance_id = $form->get('instance')->getData();
                if (isset($instance_id)) {
                    $cron->setComment($cron->getComment() . '-instance_id:' . $instance_id);
                }
                $cm->add($cron);
                $this->addFlash('message', $cm->getOutput());
                $this->addFlash('error', $cm->getError());
                if (isset($instance_id)) {
                    return $this->redirect($this->generateUrl('cron_admin', array('id' => $instance_id)));
                } else {
                    return $this->redirect($this->generateUrl('cron_index'));
                }
            } else {
                return $this->render('ConfigOrionBundle:cron/Default:create.html.twig', array(
                    'form' => $form->createView(),
                ));
            }
        }

        return $this->render('ConfigOrionBundle:cron/Default:index.html.twig', array(
            'crons' => $cm->get(),
            'raw' => $cm->getRaw(),
        ));
    }

    /**
     * Edit a cron
     *
     * @param $id The line of the cron in the cron table
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function createAction()
    {

        $form = $this->createForm(new CronType(), new Cron());

        return $this->render('ConfigOrionBundle:cron/Default:create.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Edit a cron
     *
     * @param $id The line of the cron in the cron table
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function createOfInstanceAction($id)
    {
        // Inicializando el Administrador de Documentos
        $dm = $this->getDocumentManager();

        $instancia = $dm->getRepository('ConfigOrionBundle:instancia')->find($id);

        if (!$instancia) {
            throw $this->createNotFoundException('La instancia seleccionada ya no se encuentra disponible en la base de datos.');
        }

        $form = $this->createForm(new CronType(), new Cron());

        return $this->render('ConfigOrionBundle:cron/Default:create.html.twig', array(
            'form' => $form->createView(),
            'instancia' => $instancia,
        ));
    }

    /**
     * Edit a cron
     *
     * @param $id The line of the cron in the cron table
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function editAction($id)
    {
        $cm = new CronManager();
        $crons = $cm->get();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());

        //Clean the instance_id signal
        if (\strpos($crons[$id]->getComment(), '-instance_id:') !== FALSE) {
            $instancia_id = \substr($crons[$id]->getComment(), -24, 24);
            $signal = \substr($crons[$id]->getComment(), -37, 37);
            $crons[$id]->setComment(substr_replace($crons[$id]->getComment(), "", -37));
        }

        $form = $this->createForm(new CronType(), $crons[$id]);

        $request = $this->get('request');
        if ('POST' == $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                if (isset($signal)) {
                    $crons[$id]->setComment($form->get('comment')->getData() . $signal);
                }
                $cm->write();

                $this->addFlash('message', $cm->getOutput());
                $this->addFlash('error', $cm->getError());
                if (isset($instancia_id)) {
                    return $this->redirect($this->generateUrl('cron_admin', array('id' => $instancia_id)));
                } else {
                    return $this->redirect($this->generateUrl('cron_index'));
                }
            }
        }
        if (isset($instancia_id)) {
            return $this->render('ConfigOrionBundle:cron/Default:edit.html.twig', array(
                'form' => $form->createView(),
                'instancia_id' => $instancia_id,
            ));
        } else {
            return $this->render('ConfigOrionBundle:cron/Default:edit.html.twig', array(
                'form' => $form->createView(),
            ));
        }
    }

    /**
     * Wake up a cron from the cron table
     *
     * @param $id The line of the cron in the cron table
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse
     */
    public function wakeupAction($id, $id_instancia = null)
    {
        $cm = new CronManager();
        $crons = $cm->get();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());
        $crons[$id]->setSuspended(false);
        $cm->write();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());

        if ($id_instancia !== null) {
            return $this->redirect($this->generateUrl('cron_admin', array('id' => $id_instancia)));
        } else {
            return $this->redirect($this->generateUrl('cron_index'));
        }
    }

    /**
     * Suspend a cron from the cron table
     *
     * @param $id The line of the cron in the cron table
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse
     */
    public function suspendAction($id, $id_instancia = null)
    {
        $cm = new CronManager();
        $crons = $cm->get();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());
        $crons[$id]->setSuspended(true);
        $cm->write();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());

        if ($id_instancia !== null) {
            return $this->redirect($this->generateUrl('cron_admin', array('id' => $id_instancia)));
        } else {
            return $this->redirect($this->generateUrl('cron_index'));
        }
    }

    /**
     * Remove a cron from the cron table
     *
     * @param $id The line of the cron in the cron table
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse
     */
    public function removeAction($id, $id_instancia = null)
    {
        $cm = new CronManager();
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());
        $cm->remove($id);
        $this->addFlash('message', $cm->getOutput());
        $this->addFlash('error', $cm->getError());

        if ($id_instancia !== null) {
            return $this->redirect($this->generateUrl('cron_admin', array('id' => $id_instancia)));
        } else {
            return $this->redirect($this->generateUrl('cron_index'));
        }
    }

    /**
     * Gets a log file
     *
     * @param $id The line of the cron in the cron table
     * @param $type The type of file, log or error
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fileAction($id, $type)
    {
        $cm = new CronManager();
        $crons = $cm->get();
        $cron = $crons[$id];

        $data = array();
        $data['file'] = ($type == 'log') ? $cron->getLogFile() : $cron->getErrorFile();
        $data['content'] = \file_get_contents($data['file']);

        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));

        return new Response($serializer->serialize($data, 'json'));
    }

    /**
     * Adds a flash to the flash bag where flashes are array of messages
     *
     * @param $type
     * @param $message
     * @return mixed
     */
    private function addFlash($type, $message)
    {
        if ('' == $message || null === $message) {
            return;
        }

        /* @var $session \Symfony\Component\HttpFoundation\Session */
        $session = $this->get('session');

        $session->getFlashBag()->add($type, $message);
    }


    /**
     * Obtiene el DocumentManager
     *
     * @return DocumentManager
     */
    private function getDocumentManager()
    {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }

}
