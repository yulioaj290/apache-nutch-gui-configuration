<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class archivoDeleteGenericPropertyType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('deleteGenericProperty', 'hidden', array('label' => '', 'required' => false, 'property_path' => false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        
    }

    public function getName() {
        return 'configorion_configorionbundle_archivodeletegenericpropertytype';
    }

}
