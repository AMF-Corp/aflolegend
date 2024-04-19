<?php

namespace App\Controller;

// use App\Entity\Carte;

use App\Entity\Carte;
use App\Entity\Deck;
use App\Repository\CarteRepository;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CarteController extends AbstractController
{
    #[Route('/carte', name: 'app_carte')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $graphqlEndpoint = 'https://api.tcgdex.net/v2/graphql';

        $query = <<<'GRAPHQL'
        {
            cards{
                hp
                description
                id
                category
                name
                image
            }
        }
        GRAPHQL;

        $client = HttpClient::create();
        try {
            $response = $client->request('POST', $graphqlEndpoint, [
                'json' => ['query' => $query],
            ]);

            $content = json_decode($response->getContent(), true);

            $cards = $content['data']['cards'] ?? [];

            foreach ($cards as $card) {
                $carte = new Carte();
                $carte->setCategory($card['category']);
                $carte->setDescription($card['description']);
                $carte->setHp($card['hp']);
                $carte->setImageUrl($card['image'] ?? "");

                $entityManager->persist($carte);
            }

            // Sauvegarde bdd
            $entityManager->flush();

            return new Response('Cards imported successfully!', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('Erreur : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/carte/parse', name: 'app_carte_parse')]
    public function parseCarte(EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        // URL de votre serveur GraphQL
        $graphqlEndpoint = 'https://api.tcgdex.net/v2/graphql';

        // Requête GraphQL
        $query = <<<'GRAPHQL'
        {
            
            card(id:"base4-1",filters:{id:"hgss4-1"}){
                name
                category
                description
                hp
                id
                image
              }
            
        }
        GRAPHQL;

        // Créer une instance de HttpClient
        $client = HttpClient::create();

        try {
            // Envoyer la requête POST à votre serveur GraphQL
            $response = $client->request('POST', $graphqlEndpoint, [
                'json' => [
                    'query' => $query,
                ],
            ]);

            // Récupérer le contenu de la réponse
            $content = $response->getContent();
            // $dataCard = $serializer = json_decode($content);

            // $cards = $serializer->deserialize(json_encode($dataCard->data->card), 'App\Entity\Carte[]', 'json');
            // $carte = $serializer->deserialize($content, Carte::class, "json", []);
            $dataCard = json_decode($content);
            $cards = $serializer->deserialize(json_encode($dataCard->data->card), 'App\Entity\Carte[]', 'json');



            // dd($cards);

            foreach ($cards as $card) {
                $entityManager->persist($card);
            }

            $entityManager->flush();


            // Afficher la réponse
            return new Response($content, Response::HTTP_OK);
        } catch (\Exception $e) {
            // Gérer les erreurs
            return new Response('Erreur : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/carte/view/{page}', name: 'app_carte_view', methods: ['GET'], defaults: ['page' => 1])]
    public function view(int $page = 1, SerializerInterface $serializer): Response
    {
        $apiEndpoint = 'https://api.tcgdex.net/v2/graphql';
        $itemsPerPage = 45;

        $query = <<<GRAPHQL
    {
        cards(pagination: { page: $page, itemsPerPage: $itemsPerPage }) {
            hp
            description
            id
            category
            name
            image
        }
    }
    GRAPHQL;

        $client = HttpClient::create();

        try {
            $response = $client->request('POST', $apiEndpoint, [
                'json' => ['query' => $query],
            ]);

            $content = $response->getContent();
            $data = json_decode($content, true);
            $cards = $data['data']['cards'];

            $filteredCards = [];
            foreach ($cards as $card) {
                if (!empty($card['image']) && !empty($card['name'])) {
                    $card['image'] = $this->formatImageUrl($card['image']);
                    $filteredCards[] = $card;
                }
            }

            return $this->render('carte/view.html.twig', [
                'cartes' => $filteredCards,
                'currentPage' => $page,
                'nextPage' => $page + 1,
                'prevPage' => $page > 1 ? $page - 1 : 1
            ]);
        } catch (\Exception $e) {
            return new Response('Erro: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function formatImageUrl($url)
    {
        return "https://www.tcgdex.fr/_next/image?url=" . urlencode($url) . "/low.webp&w=384&q=75";
    }


    #[Route('/user/decks', name: 'app_user_decks')]
    public function userDecks(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $decks = $entityManager->getRepository(Deck::class)->findBy(['user' => $user]);

        return $this->render('decks/index.html.twig', [
            'decks' => $decks
        ]);
    }
}
