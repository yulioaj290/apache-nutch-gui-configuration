<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class archivoNewGenericPropertyType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('newGenericRuta', 'text', array('label' => 'Ruta de etiqueta padre', 'required' => false, 'read_only' => true, 'property_path' => false, 'attr' => array('class' => 'input-large')))
                ->add('newGenericProperty', 'textarea', array('label' => 'Estructura de la propiedad', 'required' => false, 'property_path' => false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        
    }

    public function getName() {
        return 'configorion_configorionbundle_archivonewgenericpropertytype';
    }

}
