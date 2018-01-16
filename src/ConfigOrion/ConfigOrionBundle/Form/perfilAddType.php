<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ODM\MongoDB\DocumentRepository;

class perfilAddType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('id_modificacion', 'hidden')
                ->add('id_perfil', 'document', array('label' => 'Perfil:',
                    'class' => 'ConfigOrionBundle:perfil',
                    'query_builder' => function (DocumentRepository $dr) {
                return $dr->createQueryBuilder();
            }, 'required' => true, 'empty_value' => false,
                        )
        );
        
    }

    public function getName() {
        return 'form_perfiles';
    }

}
