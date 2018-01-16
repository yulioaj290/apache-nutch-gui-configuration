<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class archivarModificacionType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('dateModificacion', 'text', array('label' => 'Fecha de antiguedad', 'required' => false, 'mapped' => false, 'attr' => array('class' => 'form-control')))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        
    }

    public function getName() {
        return 'configorion_configorionbundle_archivarmodificaciontype';
    }

}
