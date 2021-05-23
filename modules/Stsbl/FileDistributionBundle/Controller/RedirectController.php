<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/filedistribution/rooms", name="admin_filedistribution_room_legacy_redirect")
 */
final class RedirectController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->redirectToRoute('admin_fd_filedistribution_room_index', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
