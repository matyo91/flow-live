<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Seo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WaveFunctionCollapseController extends AbstractController
{
    #[Route('/wave-function-collapse', name: 'app_wave_function_collapse')]
    public function index(): Response
    {
        return $this->render('base.html.twig', [
            'seo' => new Seo('wave-function-collapse', 'Wave Function Collapse'),
        ]);
    }
}
