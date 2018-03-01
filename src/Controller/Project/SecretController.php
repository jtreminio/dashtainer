<?php

namespace Dashtainer\Controller\Project;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Response\AjaxResponse;
use Dashtainer\Validator;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecretController extends Controller
{
    /**
     * @Route(name="project.secret.create.post",
     *     path="/project/{projectId}/secret/create",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @return AjaxResponse
     */
    public function postCreate(
        Request $request,
        Entity\User $user,
        string $projectId
    ) : AjaxResponse {
        //
    }

    /**
     * @Route(name="project.secret.view.get",
     *     path="/project/{projectId}/secret/{secretId}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $secretId
     * @return Response
     */
    public function getView(
        Entity\User $user,
        string $projectId,
        string $secretId
    ) : Response {
        //
    }

    /**
     * @Route(name="project.secret.update.get",
     *     path="/project/{projectId}/secret/{secretId}/update",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $secretId
     * @return Response
     */
    public function getUpdate(
        Entity\User $user,
        string $projectId,
        string $secretId
    ) : Response {
        //
    }

    /**
     * @Route(name="project.secret.update.post",
     *     path="/project/{projectId}/secret/{secretId}/update",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $secretId
     * @return AjaxResponse
     */
    public function postUpdate(
        Request $request,
        Entity\User $user,
        string $projectId,
        string $secretId
    ) : AjaxResponse {
        //
    }

    /**
     * @Route(name="project.secret.delete.post",
     *     path="/project/{projectId}/secret/{secretId}/delete",
     *     methods={"POST"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $secretId
     * @return Response
     */
    public function postDelete(
        Entity\User $user,
        string $projectId,
        string $secretId
    ) : Response {
        //
    }
}
