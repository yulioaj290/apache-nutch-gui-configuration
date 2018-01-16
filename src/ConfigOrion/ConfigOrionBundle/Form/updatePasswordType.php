<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class updatePasswordType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('passwordActual', 'password', array('label' => 'Clave actual', 'property_path' => false))
                ->add('password', 'repeated', array(
                    'first_name' => 'Clave',
                    'second_name' => 'Repetir',
                    'type' => 'password',
                ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\Document\usuario'
        ));
    }

    public function getName() {
        return 'configorion_configorionbundle_updatepasswordtype';
    }

}
