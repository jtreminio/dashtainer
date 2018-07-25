<?php

namespace Dashtainer\Controller\Project\Service;

use Dashtainer\Response\AjaxResponse;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class Php extends Controller
{
    /**
     * @Route(name="project.service.php.ini.get",
     *     path="/project/{projectId}/service/php/ini",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getVhost() : AjaxResponse
    {
        $template = '@Dashtainer/project/service/php-fpm/ini.html.twig';

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => $this->renderView($template, [
                'id'  => uniqid(),
                'var' => [
                    'ini'   => '',
                    'env'   => '',
                    'value' => '',
                ],
            ]),
        ], AjaxResponse::HTTP_OK);
    }
}
