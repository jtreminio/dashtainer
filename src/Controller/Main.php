<?php

namespace Dashtainer\Controller;

use Dashtainer\Entity\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
        if (!$user) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->redirectToRoute('project.index.get');
    }
}
