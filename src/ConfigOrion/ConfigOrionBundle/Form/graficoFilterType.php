<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use \Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;

class graficoFilterType extends AbstractType {
    private $choiceList;
    
    function __construct($choiceList) {
        $this->choiceList = $choiceList;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $choiceList = new SimpleChoiceList($this->getChoiceList());
        $builder
                ->add('cantidad', 'text', array('label' => 'Últimas modificaciones', 'required' => false, 'attr' => array('class' => 'form-control form-filter-cantidad', 'placeholder' => '[Cantidad]')))
                ->add('archivo', 'choice', array('label' => 'Instancia | Plugin | Archivo', 'choice_list' => $choiceList, 'required' => false, 'attr' => array('class' => 'form-control-except form-filter-archivo')))
                ->add('fecha', 'text', array('label' => 'Rango de fecha', 'read_only' => true, 'required' => false, 'attr' => array('class' => 'form-control form-filter-fecha', 'placeholder' => '[Fecha inicio] - [Fecha fin]', 'style' => 'background-color: #FFFFFF; cursor: pointer;')))
        ;
    }

    public function getName() {
        return 'configorion_configorionbundle_graficofiltertype';
    }

    public function getChoiceList() {
        return $this->choiceList;
    }

    public function setChoiceList($choiceList) {
        $this->choiceList = $choiceList;
    }


}
