<?php
// Esta clase ha sido creada duplicando la clase archivoType.php
// con el objetivo de poder requerir el campo descripcionModificacion en la vista edit.html.twig

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class archivoUpdateDataType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('descripcion', 'textarea', array('label'=>'Descripción','required' => false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\Document\archivo'
        ));
    }

    public function getName() {
        return 'configorion_configorionbundle_archivoupdatetype';
    }

}
