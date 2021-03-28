<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;


class UserController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/profile", name="user_profile")
     */
    public function index(Request $request, SluggerInterface $slugger): Response {

        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatarImage */
            $avatarImage = $form->get('avatar')->getData();

            if ($avatarImage) {
                $originalFilename = pathinfo($avatarImage->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarImage->guessExtension();

                try {
                    $avatarImage->move(
                        $this->getParameter('avatars_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }

                $user->setAvatarFilename($newFilename);
            }

            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }

            return $this->render('user/profile.html.twig', [
                'form' => $form->createView(),
                'avatarFilename' => $user->getAvatarFilename(),
            ]);
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
            'avatarFilename' => $user->getAvatarFilename(),
        ]);
    }
}
