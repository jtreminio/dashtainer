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

class VolumeController extends Controller
{
    /**
     * @Route(name="project.volume.create.post",
     *     path="/project/{projectId}/volume/create",
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
     * @Route(name="project.volume.view.get",
     *     path="/project/{projectId}/volume/{volumeId}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $volumeId
     * @return Response
     */
    public function getView(
        Entity\User $user,
        string $projectId,
        string $volumeId
    ) : Response {
        //
    }

    /**
     * @Route(name="project.volume.update.get",
     *     path="/project/{projectId}/volume/{volumeId}/update",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $volumeId
     * @return Response
     */
    public function getUpdate(
        Entity\User $user,
        string $projectId,
        string $volumeId
    ) : Response {
        //
    }

    /**
     * @Route(name="project.volume.update.post",
     *     path="/project/{projectId}/volume/{volumeId}/update",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $volumeId
     * @return AjaxResponse
     */
    public function postUpdate(
        Request $request,
        Entity\User $user,
        string $projectId,
        string $volumeId
    ) : AjaxResponse {
        //
    }

    /**
     * @Route(name="project.volume.delete.post",
     *     path="/project/{projectId}/volume/{volumeId}/delete",
     *     methods={"POST"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $volumeId
     * @return Response
     */
    public function postDelete(
        Entity\User $user,
        string $projectId,
        string $volumeId
    ) : Response {
        //
    }
}
