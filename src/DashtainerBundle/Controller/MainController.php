<?php

namespace DashtainerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class MainController extends Controller
{
    /**
     * @Route(name="index", path="/")
     */
    public function indexAction()
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserByUsername('test@dashtainer.com');

        return $this->render('@Dashtainer/index.html.twig');
    }
}
