<?php

namespace ConfigOrion\ConfigOrionBundle\Form;

use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CronType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('minute');
        $builder->add('hour');
        $builder->add('dayOfMonth');
        $builder->add('month');
        $builder->add('dayOfWeek');

        $builder->add('command');
        $builder->add('logFile', 'text', array(
            'required' => false,
        ));
        $builder->add('errorFile', 'text', array(
            'required' => false,
        ));
        $builder->add('comment', 'text', array(
            'required' => false,
        ));
        $builder->add('instance', 'hidden', array(
            'required' => false, 'mapped'=>false,
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ConfigOrion\ConfigOrionBundle\ExternalClases\Cron'
        ));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    function getName()
    {
        return 'cron';
    }
}
