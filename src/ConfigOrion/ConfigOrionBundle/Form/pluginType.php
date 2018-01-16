<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class pluginType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder                
                ->add('ruta', 'text', array('attr'=>array('autocomplete'=>'off', 'class'=>'ui-autocomplete-input')))
                ->add('descripcion', 'textarea', array('label'=>'Descripción','required' => false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\Document\plugin'
        ));
    }

    public function getName() {
        return 'configorion_configorionbundle_plugintype';
    }

}
