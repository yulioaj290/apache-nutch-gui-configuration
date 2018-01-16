<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class archivoType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $choiceList = new SimpleChoiceList(array(
            'XML' => 'XML',
            'TXT' => 'TXT',
            'NULL' => 'Otra extensión',
        ));
        $builder
                ->add('nombre')
                ->add('ruta', 'text', array('attr'=>array('autocomplete'=>'off', 'class'=>'ui-autocomplete-input')))
                ->add('formato', 'choice', array('choice_list' => $choiceList))
                ->add('contenido', 'textarea', array('required' => false))
                ->add('descripcion', 'textarea', array('label'=>'Descripción','required' => false))
                ->add('descripcionModificacion', 'textarea', array('label'=>'Descripción de modificación','required' => false, 'property_path' => false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\Document\archivo'
        ));
    }

    public function getName() {
        return 'configorion_configorionbundle_archivotype';
    }

}
