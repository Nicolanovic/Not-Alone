<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        $user = $this->getUser();
        $avatarFilename = $user->getAvatarFilename();
        $notice=false;

        return $this->render('index/index.html.twig', [
            'avatarFilename' => $avatarFilename,
            'notice' => $notice
        ]);
    }
}
