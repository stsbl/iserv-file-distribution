<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/Traits/InternetTimeFormTrait.php
namespace Stsbl\FileDistributionBundle\Crud\Batch\Traits;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
 * Common trait which adds internet-until form handling to the batch action
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensourc.org/licenses/MIT>
 */
trait InternetTimeFormTrait 
{
    /**
     * @var integer|string
     */
    protected $until;
    
    /**
     * Allows the batch action to manipulate the form.
     *
     * This is called at the end of `prepareBatchActions`.
     *
     * @param FormInterface $form
     */
    public function finalizeForm(FormInterface $form)
    {
        $form
            ->add('inet_duration', ChoiceType::class, [
                'label' => _('Duration'),
                'constraints' => [
                    new NotBlank(['message' => _('Please select the duration.')])
                ],
                'choices' => [
                    __n('One minute', '%d minutes', 15, 15) => 15,
                    __n('One minute', '%d minutes', 30, 30) => 30,
                    __n('One minute', '%d minutes', 45, 45) => 45,
                    __n('One hour', '%d hours', 1, 1) => 60,
                    __n('One hour', '%d hours', 1.5, 1.5) => 90,
                    __n('One hour', '%d hours', 3, 3) => 180,
                    _('Today') => 'today'
                ],
                'attr' => [
                    'required' => 'required'
                ]
            ])
        ;
    }
    
    /**
     * Gets called with the full form data instead of `execute`.
     *
     * @param array $data
     */
    public function handleFormData(array $data)
    {
        $this->until = $data['inet_duration'];
        return $this->execute($data['multi']);
    }
}
