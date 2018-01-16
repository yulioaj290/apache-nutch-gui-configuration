<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class modificacionType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('fecha', null, array('widget' => 'single_text'))
                ->add('propiedad')
                ->add('rutaPropiedad', 'text', array('label' => 'Ruta Propiedad'))
                ->add('valorAnterior', 'text', array('label' => 'Valor Anterior'))
                ->add('valorActual', 'text', array('label' => 'Valor Actual'))
                ->add('descripcion', 'textarea', array('label'=>'Descripción'))

        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\Document\modificacion'
        ));
    }

    public function getName() {
        return 'configorion_configorionbundle_modificaciontype';
    }

}
