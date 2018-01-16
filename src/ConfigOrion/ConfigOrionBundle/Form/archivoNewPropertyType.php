<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class archivoNewPropertyType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nameNewProperty', 'text', array('label' => 'Nombre', 'required' => false, 'property_path' => false))
                ->add('valueNewProperty', 'text', array('label' => 'Valor', 'required' => false, 'property_path' => false))
                ->add('descNewProperty', 'textarea', array('label' => 'Descripción', 'required' => false, 'property_path' => false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        
    }

    public function getName() {
        return 'configorion_configorionbundle_archivonewpropertytype';
    }

}
