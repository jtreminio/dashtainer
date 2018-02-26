<?php

namespace DashtainerBundle\Controller;

use DashtainerBundle\Entity\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MainController extends Controller
{
    /**
     * @param User $user
     * @return Response
     * @Route(name="index.get",
     *     path="/",
     *     methods={"GET"}
     * )
     */
    public function indexGetAction(User $user = null) : Response
    {
        if (!$user) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->redirectToRoute('project.index.get');
    }
}
