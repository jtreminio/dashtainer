<?php

namespace Dashtainer\Controller\Project\Service;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Response\AjaxResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class NginxController extends Controller
{
    /** @var Repository\DockerProjectRepository */
    protected $dProjectRepo;

    public function __construct(
        Repository\DockerProjectRepository $dProjectRepo
    ) {
        $this->dProjectRepo = $dProjectRepo;
    }

    /**
     * @Route(name="project.service.nginx.vhost.post",
     *     path="/project/{projectId}/service/nginx/vhost/{type}",
     *     methods={"POST"}
     * )
     * @param Request     $request,
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $type
     * @return AjaxResponse
     */
    public function postVhost(
        Request $request,
        Entity\User $user,
        string $projectId,
        string $type
    ) : AjaxResponse {
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form = new Form\Service\NginxVhost();
        $form->fromArray($request->request->all());

        $template = "@Dashtainer/project/service/nginx/vhost-{$type}.conf.twig";

        try {
            $content = $this->renderView($template, [
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            $content = '';
        }

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => $content,
        ], AjaxResponse::HTTP_OK);
    }
}
