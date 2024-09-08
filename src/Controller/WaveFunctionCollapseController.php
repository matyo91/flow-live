<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WaveFunctionCollapseController extends AbstractController
{
    #[Route('/wave/function/collapse', name: 'app_wave_function_collapse')]
    public function index(): Response
    {
        return $this->render('wave_function_collapse/index.html.twig', [
            'controller_name' => 'WaveFunctionCollapseController',
        ]);
    }
}
