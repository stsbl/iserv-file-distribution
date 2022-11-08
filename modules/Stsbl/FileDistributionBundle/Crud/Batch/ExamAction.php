<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ExamAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    /**
     * {@inheritDoc}
     */
    protected $privileges = Privilege::EXAM;

    /**
     * @var string
     */
    private $title;

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
            ->add('exam_title', TextType::class, [
                'label' => _('Exam title'),
                'constraints' => [
                    new NotBlank(['message' => _('Please enter a title for your exam.')])
                ],
                'attr' => [
                    'placeholder' => _('Title for this exam'),
                    'required' => 'required'
                ]
            ])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function handleFormData(array $data): FlashMessageBag
    {
        $this->title = $data['exam_title'];

        return $this->execute($data['multi']);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'exam';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Enable');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Switch the selected computers into exam mode.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'pro-disk-open';
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(): string
    {
        return _('Exam Mode');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        /** @var FileDistribution[] $entities */
        $hosts = [];

        foreach ($entities as $entity) {
            $hosts[] = $entity->getHost();
        }

        $messages = [];
        $error = false;

        foreach ($hosts as $key => $entity) {
            $skipOwnHost = false;

            if (null === $this->title || '' === $this->title) {
                $messages[] = $this->createFlashMessage('error', _('Title should not be empty!'));
                $error = true;
                break;
            }

            $request = $this->crud->request();
            $clientIp = $request ? $request->getClientIp() : null;

            if (null === $clientIp || ($entity->getIp() === $clientIp && count($entities) > 1)) {
                $messages[] = $this->createFlashMessage('warning', _('Skipping own host!'));
                unset($hosts[$key]);
                $skipOwnHost = true;
            }

            if (!$skipOwnHost) {
                $messages[] = $this->createFlashMessage('success', __('Switched %s to exam mode.', (string)$entity->getName()));
            }
        }

        if (!$error) {
            $bag = $this->getFileDistributionManager()->examOn($hosts, $this->title);
        } else {
            $bag = new FlashMessageBag();
        }
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user): bool
    {
        return $this->crud->authorizationChecker()->isGranted(Privilege::EXAM);
    }
}
