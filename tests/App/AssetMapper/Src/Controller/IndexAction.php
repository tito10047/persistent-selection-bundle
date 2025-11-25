<?php

namespace Tito10047\PersistentSelectionBundle\Tests\App\AssetMapper\Src\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class IndexAction extends AbstractController{


    #[Route('/', name: 'index')]
    public function __invoke()
    {

        return $this->render('index.html.twig',[
        ]);
    }

}
