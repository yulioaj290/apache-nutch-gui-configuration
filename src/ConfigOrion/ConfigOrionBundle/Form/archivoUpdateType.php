<?php
// Esta clase ha sido creada duplicando la clase archivoType.php
// con el objetivo de poder requerir el campo descripcionModificacion en la vista edit.html.twig

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class archivoUpdateType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('contenido', 'textarea', array('required' => false))
                ->add('descripcionModificacion', 'textarea', array('label'=>'Descripción de modificación','required' => true, 'property_path' => false))
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
