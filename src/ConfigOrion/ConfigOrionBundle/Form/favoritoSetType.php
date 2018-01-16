<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ODM\MongoDB\DocumentRepository;

class favoritoSetType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder                                                       
                ->add('id_instancia', 'document', array('label' => 'Instancia:',
                    'class' => 'ConfigOrionBundle:instancia',
                    'query_builder' => function (DocumentRepository $dr) {
                return $dr->createQueryBuilder();
            }, 'required' => true, 'empty_value' => false,
                        )
        );        
    }

    public function getName() {
        return 'favorito_instance';
    }

}
