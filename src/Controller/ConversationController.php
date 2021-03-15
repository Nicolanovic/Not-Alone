<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Participant;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
* @Route("/conversations", name="conversations.")
*/
class ConversationController extends AbstractController
{

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ConversationRepository
     */
    private $conversationRepository;

    public function __construct(UserRepository $userRepository,
                                EntityManagerInterface $entityManager,
                                ConversationRepository $conversationRepository)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * @Route("/", name="getConversations", methods={"GET"})
     */
    public function getConvs() {

        $conversations = $this->conversationRepository->findConversationsByUser($this->getUser()->getId());

        $serializer = new Serializer([new ObjectNormalizer()]);

        $conversationsJson = $serializer->normalize($conversations, null, [AbstractNormalizer::ATTRIBUTES => ['username', 'conversationId', 'content', 'createdAt']]);

        return $this->render('conversation/index.html.twig', [
            'conversations' => $conversationsJson,
        ]);
    }
    
    /**
     * @Route("/{id}", name="showConversation", methods={"GET", "POST"})
     * @param Request $request
     * @param Conversation $conversation
     */
    public function showConv(Request $request, Conversation $conversation, MessageRepository $messageRepository) {

        $newMessage = new Message();
        $form = $this->createForm(MessageType::class, $newMessage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $newMessage->setUser($this->getUser());
            $newMessage->setConversation($conversation);
            $newMessage->setMine(true);

            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->entityManager->persist($newMessage);
                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }

        }

        // On cherche l'autre participant
        $conversations = $this->conversationRepository->findConversationsByUser($this->getUser()->getId());

        foreach ($conversations as $_conversation) {
            if ($_conversation["conversationId"] == $conversation->getId()) {
                $otherParticipant = $_conversation["username"];
            }
        }

        $messages = $messageRepository->findMessageByConversationId($conversation->getId());

        /**
         * @var $message Message
         */
        array_map(function ($message) {
            $message->setMine(
                $message->getUser()->getId() === $this->getUser()->getId()
                    ? true : false
            );
        }, $messages);

        $serializer = new Serializer([new ObjectNormalizer()]);

        $messagesJson = $serializer->normalize($messages, null, [AbstractNormalizer::ATTRIBUTES => ['id', 'content', 'createdAt', 'mine']]);

        return $this->render('conversation/conversation.html.twig', [
            'messages' => $messagesJson,
            'otherParticipant' => $otherParticipant,
            'messageForm' => $form->createView()
        ]);
    }

}
