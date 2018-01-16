<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class loginType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('_username', 'text', array('attr' => array('autocomplete' => 'off', 'id' => 'username', 'class' => 'form-control', 'placeholder' => 'Usuario', 'autofocus' => 'true')))
                ->add('_password', 'password', array('attr' => array('autocomplete' => 'off', 'id' => 'password', 'class' => 'form-control', 'placeholder' => 'Contraseña')))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    }

    public function getName() {
        return '';
    }

}
 
