<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class etiquetaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('propiedad', 'text', array('read_only' => true, 'disabled' => true))
            ->add('rutaPropiedad', 'text', array('label'=>'Ruta Propiedad', 'read_only' => true, 'disabled' => true))
            ->add('valor', 'textarea')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\Document\etiqueta'
        ));
    }

    public function getName()
    {
        return 'configorion_configorionbundle_etiquetatype';
    }
}
