<?php
// src/Stsbl/FileDistributionBundle/Form/Type/FileDistributionType.php
namespace Stsbl\FileDistributionBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use Stsbl\FileDistributionBundle\Form\DataTransformer\FileDistributionTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Combined text field and select fiedl for setting up file distributions
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionType extends AbstractType
{
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * The constructor
     * 
     * @param SecurityHandler $securityHandler
     */
    public function __construct(SecurityHandler $securityHandler = null) 
    {
        if (is_null($securityHandler)) {
            throw new \InvalidArgumentException('securityHandler is empty. Did you forget to pass it to the constructor?');
        }
        
        $this->securityHandler = $securityHandler;
    }
    
    /**
     * @see \Symfony\Component\Form\AbstractType::buildForm()
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new FileDistributionTransformer($this->securityHandler);
        
        $builder
            ->add('hostname', EntityType::class, [
                'label' => false,
                'required' => false,
                'class' => 'StsblFileDistributionBundle:Host',
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.controllable = true');
                },
                'attr' => [
                    'placeholder' => _('Select a host for file distribution...'),
                    'help_text' => _('For the user who is logging in on this host, there will created two addtional network drives for file distribution assignment and return.')
                ]
            ])
            ->add('title', TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'file-distribution-form-title',
                    'placeholder' => _('Enter a title for this file distribution...'),
                    'help_text' => _('The folder path where you will find the assignment folder and the returns will be Files/File-Distribution/<Title>.')
                ]
            ])
            ;
                
        $builder->addModelTransformer($transformer);
    }
    
    /**
     * @see \Symfony\Component\Form\AbstractType::configureOptions()
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Stsbl\FileDistributionBundle\Entity\FileDistribution'
        ]);
    }
    
    /**
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getBlockPrefix()
    {
        return 'filedistribution_filedistribution';
    }
}
