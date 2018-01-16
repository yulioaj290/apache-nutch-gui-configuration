<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class uploadType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('archivo', 'file');
    }

    public function getName() {
        return 'favorito_import_file';
    }

}
