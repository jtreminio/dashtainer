<?php

namespace Dashtainer\Controller;

use Dashtainer\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class Main extends Controller
{
    /**
     * @Route(name="index.get",
     *     path="/",
     *     methods={"GET"}
     * )
     * @param User $user
     * @return Response
     */
    public function indexGet(User $user = null) : Response
    {
        return $this->render('@Dashtainer/index.html.twig', [
            'user' => $user,
        ]);
    }
}
