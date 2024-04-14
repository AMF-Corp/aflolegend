<?php

namespace App\Controller;

use App\Entity\Actualite;
use App\Entity\Commentaire;
use App\Form\CommentFormType;
use App\Repository\ActualiteRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ActualiteController extends AbstractController
{
    #[Route('/', name: 'app_actualite')]
    public function index(Request $request, ActualiteRepository $actualiteRepository): Response
    {
        return $this->render('actualite/index.html.twig', []);
    }

    #[Route('/show/{id}', name: 'app_actualite_show')]
    public function show(Request $request, Actualite $actu, EntityManagerInterface $entityManager): Response
    {

        $commentaire = new Commentaire();


        $form = $this->createForm(CommentFormType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setActualite($actu);
            $entityManager->persist($commentaire);
            $entityManager->flush();

            return $this->redirect($request->getUri());
        }

        return $this->render('actualite/show.html.twig', [
            'form' => $form,
            'actu' => $actu
        ]);
    }
    #[Route('/parseActu', name: 'app_actualite_parse')]
    public function parse(SerializerInterface $serializer): Response
    {
        $graphqlEndpoint = 'https://127.0.0.1:8000/api/graphql';

        $query = <<<'GRAPHQL'
        {
            actualite(id: "/api/actualites/1") {
                id
                titre
                auteur
            }
        }
        GRAPHQL;

        $client = HttpClient::create();
        try {
            $response = $client->request('POST', $graphqlEndpoint, [
                'json' => ['query' => $query],
                'headers' => [
                    'Content-Type' => 'application/json'
                ], 'verify_peer' => false,
            ]);

            $content = $response->getContent();

            // Logique de sauvegarde (à implémenter)


            return new Response($content, Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('Erreur : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
