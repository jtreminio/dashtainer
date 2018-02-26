<?php

namespace PodYardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class MainController extends Controller
{
    /**
     * @Route(name="index", path="/")
     */
    public function indexAction()
    {
        return $this->render('@PodYard/index.html.twig');
    }
}
