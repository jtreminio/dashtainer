<?php

namespace Dashtainer\Controller\Project\Service;

use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Response\AjaxResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Add extends Controller
{
    /**
     * @Route(name="project.service.add.file.get",
     *     path="/project/{projectId}/service/add/file/{highlight}",
     *     methods={"GET"}
     * )
     * @param string $highlight
     * @return AjaxResponse
     */
    public function getFile(
        string $highlight
    ) : AjaxResponse {
        $volume = new Entity\ServiceVolume();
        $volume->fromArray(['id' => uniqid()]);
        $volume->setName($volume->getId())
            ->setHighlight($highlight);

        $template = '@Dashtainer/project/service/snippets/volume-file-add-tab.html.twig';
        $tab      = $this->render($template, [
            'volume'    => $volume,
            'loopFirst' => false,
        ]);

        $template = '@Dashtainer/project/service/snippets/volume-file-add-content.html.twig';
        $content  = $this->render($template, [
            'volume'      => $volume,
            'loopFirst'   => false,
            'serviceName' => '',
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => [
                'tab'     => $tab->getContent(),
                'content' => $content->getContent(),
            ],
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.service.add.network.get",
     *     path="/project/{projectId}/service/add/network",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getNetwork() : AjaxResponse
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => uniqid()]);

        $template = '@Dashtainer/project/service/snippets/network-add.html.twig';
        $rendered = $this->render($template, [
            'network' => $network,
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => $rendered->getContent(),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.service.add.port.get",
     *     path="/project/{projectId}/service/add/port",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getPort() : AjaxResponse
    {
        $port = new Entity\ServicePort();
        $port->fromArray(['id' => uniqid()]);

        $template = '@Dashtainer/project/service/snippets/port-add.html.twig';
        $rendered = $this->render($template, [
            'port' => $port,
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => $rendered->getContent(),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.service.add.secret.get",
     *     path="/project/{projectId}/service/add/secret",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getSecret() : AjaxResponse
    {
        $projectSecret = new Entity\Secret();
        $projectSecret->fromArray(['id' => uniqid()]);

        $serviceSecret = new Entity\ServiceSecret();
        $serviceSecret->fromArray(['id' => uniqid()]);
        $serviceSecret->setProjectSecret($projectSecret);

        $template = '@Dashtainer/project/service/snippets/secret-add.html.twig';
        $rendered = $this->render($template, [
            'secret' => $serviceSecret,
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => $rendered->getContent(),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.service.add.volume.get",
     *     path="/project/{projectId}/service/add/volume",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getVolume() : AjaxResponse
    {
        $volume = new Entity\ServiceVolume();
        $volume->fromArray(['id' => uniqid()]);

        $template = '@Dashtainer/project/service/snippets/volume-add.html.twig';
        $rendered = $this->render($template, [
            'volume' => $volume,
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => $rendered->getContent(),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.service.add.volume-bind.get",
     *     path="/project/{projectId}/service/add/volume-bind",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getVolumeBind() : AjaxResponse
    {
        $volume = new Entity\ServiceVolume();
        $volume->fromArray(['id' => uniqid()]);

        $template = '@Dashtainer/project/service/snippets/volume-bind-add.html.twig';
        $rendered = $this->render($template, [
            'volume' => $volume,
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => $rendered->getContent(),
        ], AjaxResponse::HTTP_OK);
    }
}
