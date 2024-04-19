<?php

namespace App\Controller;

use App\Entity\Carte;
use App\Entity\Deck;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

class DeckController extends AbstractController
{
    #[Route('/deck/view', name: 'deck_view')]
    public function viewDecks(EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        $decks = $em->getRepository(Deck::class)->findBy(['user' => $user]);

        foreach ($decks as $deck) {
            $cartes = $deck->getCartes();
            if (!$cartes->isInitialized()) {
                $cartes->initialize();
            }
        }

        return $this->render('deck/view.html.twig', ['decks' => $decks]);
    }

    // #[Route('/deck/save', name: 'deck_save', methods: ['POST'])]
    // public function saveDeck(Request $request, EntityManagerInterface $entityManager, Security $security, SerializerInterface $serializer): Response
    // {
    //     $data = json_decode($request->getContent(), true);
    //     $user = $security->getUser();

    //     $deck = new Deck();
    //     $deck->setUser($user);
    //     $entityManager->persist($deck);
    //     $entityManager->flush();

    //     $cartes = $serializer->deserialize(json_encode($data), Carte::class . '[]', 'json');
    //     // dd($data['cartes']);
    //     foreach ($cartes as $carte) {
    //         $carte->setDeck($deck);
    //         $entityManager->persist($carte);
    //     }
    //     $entityManager->flush();


    //     return new Response('Deck saved successfully with id ' . $deck->getId());
    // }
    #[Route('/deck/save', name: 'deck_save', methods: ['POST'])]
    public function saveDeck(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $deck = new Deck();
        $deck->setUser($user);
        $entityManager->persist($deck);

        foreach ($data['cartes'] as $carteData) {
            $carte = new Carte();
            $carte->setName($carteData['name'] ?? 'Unknown Name');
            $carte->setCategory($carteData['category'] ?? 'Unknown Category');
            $carte->setDescription($carteData['description'] ?? null);
            $carte->setHp($carteData['hp'] ?? 0);
            $carte->setImageUrl($carteData['image'] ?? 'no-image.png');
            $carte->setUid($carteData['uid'] ?? 'no-uid');  // Fornecer um UID padrão ou tratar a ausência dele

            $carte->setDeck($deck);
            $entityManager->persist($carte);
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Deck saved successfully with id ' . $deck->getId()
        ], Response::HTTP_OK);
    }






    #[Route('/deck/delete/{id}', name: 'deck_delete', methods: ['DELETE'])]
    public function deleteDeck(Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        $deck = $entityManager->getRepository(Deck::class)->find($id);
        if (!$deck) {
            return $this->json([
                'success' => false,
                'message' => 'Deck not found.'
            ], Response::HTTP_NOT_FOUND);  // 404 Not Found response
        }

        try {
            foreach ($deck->getCartes() as $carte) {
                $entityManager->remove($carte);  // Remove associated cartes first
            }
            $entityManager->remove($deck);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Deck deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting deck: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);  // 500 Internal Server Error
        }
    }
}
