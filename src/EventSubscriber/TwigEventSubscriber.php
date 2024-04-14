<?php

namespace App\EventSubscriber;

use App\Repository\ActualiteRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    private $actualiteRepository;
    private $twig;

    public function __construct(ActualiteRepository $actualiteRepository,Environment $twig)
    {
        $this->actualiteRepository = $actualiteRepository;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
       $actualites = $this->actualiteRepository->findAll();
       $this->twig->addGlobal('actus',$actualites);
       
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
