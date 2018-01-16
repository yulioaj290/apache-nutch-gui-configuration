<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class usuarioType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $choiceList = new SimpleChoiceList(array(
            'ROLE_USER' => 'ROLE_USER',
            'ROLE_ADMIN' => 'ROLE_ADMIN',
            'ROLE_SUPER_ADMIN' => 'ROLE_SUPER_ADMIN'
        ));
        $builder
                ->add('nombre', 'text', array('label' => 'Nombre completo'))
                ->add('username', 'text', array('label' => 'Nombre Usuario'))
//                ->add('roles', 'choice', array('choice_list' => $choiceList))
                ->add('roles', 'collection', array(
                    'type' => 'choice',
                    'label'=>' ',
                    'options' => array(
                        'label'=>'Rol',
                        'choices'=>array(
                            'ROLE_USER'=>'ROLE_USER',
                            'ROLE_ADMIN'=>'ROLE_ADMIN',
                            'ROLE_SUPER_ADMIN'=>'ROLE_SUPER_ADMIN'
                        )
                    ), 'allow_add'=>true, 'allow_delete'=>true, 'prototype'=>true, 'prototype_name'=>'0'
                ))
                //->add('passwordActual', 'password', array('label' => 'Clave actual', 'mapped' => false, 'required' => false))
                ->add('password', 'repeated', array(
                    'first_name' => 'Clave',
                    'second_name' => 'Repetir',
                    'type' => 'password',
                    'required' => false,
                ))
                ->add('email', 'email', array('required' => true))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\Document\usuario'
        ));
    }

    public function getName() {
        return 'configorion_configorionbundle_usuariotype';
    }

}
