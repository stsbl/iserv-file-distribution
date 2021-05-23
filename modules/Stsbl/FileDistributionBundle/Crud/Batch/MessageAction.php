<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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
 * file distribution rpc message batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class MessageAction extends AbstractFileDistributionAction
{
    protected $privileges = Privilege::BOOT;

    /**
     * @var string
     */
    private $message;

    /**
     * {@inheritDoc}
     */
    public function finalizeForm(FormInterface $form): void
    {
        $form
            ->add('rpc_message', TextareaType::class, [
                'label' => _('Message'),
                'attr' => [
                    'rows' => 10,
                    'cols' => 230,
                    'placeholder' => _('Enter a message...')
                ],
                'constraints' => [
                    new NotBlank()
                ]
            ])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function handleFormData(array $data): FlashMessageBag
    {
        $this->message = $data['rpc_message'];

        return $this->execute($data['multi']);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        if ($this->message === null) {
            throw new \InvalidArgumentException('No message set!');
        }

        $messages = [];

        /* @var Host[] $entities */
        foreach ($entities as $entity) {
            $messages[] = $this->createFlashMessage('success', __('Sent message to %s.', (string)$entity->getName()));
        }

        $bag = $this->getFileDistributionManager()->msg($entities, $this->message);
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'message';
    }

    /**
     * {@inheritodc}
     */
    public function getLabel(): string
    {
        return _('Send message');
    }

    /**
     * {@inheritdoc}
     */
    public function getTooltip(): string
    {
        return _('Send message to the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon(): string
    {
        return 'pro-message-full';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmClass(): string
    {
        return 'primary';
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup(): string
    {
        return _('Communication');
    }
}
