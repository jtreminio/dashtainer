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

class NetworkController extends Controller
{
    /** @var Domain\DockerNetwork */
    protected $dockerNetworkDomain;

    /** @var Repository\DockerProjectRepository */
    protected $dProjectRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\DockerNetwork $dockerNetworkDomain,
        Repository\DockerProjectRepository $dProjectRepo,
        Validator\Validator $validator
    ) {
        $this->dockerNetworkDomain = $dockerNetworkDomain;

        $this->dProjectRepo = $dProjectRepo;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.network.create.post",
     *     path="/project/{projectId}/network/create",
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
        $form = new Form\DockerNetworkCreateUpdateForm();
        $form->fromArray($request->request->all());

        $form->project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $network = $this->dockerNetworkDomain->createNetworkFromForm($form);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.network.view.get', [
                'projectId' => $form->project->getId(),
                'networkId' => $network->getId(),
            ]),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.network.view.get",
     *     path="/project/{projectId}/network/{networkId}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $networkId
     * @return Response
     */
    public function getView(
        Entity\User $user,
        string $projectId,
        string $networkId
    ) : Response {
        //
    }

    /**
     * @Route(name="project.network.update.get",
     *     path="/project/{projectId}/network/{networkId}/update",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $networkId
     * @return Response
     */
    public function getUpdate(
        Entity\User $user,
        string $projectId,
        string $networkId
    ) : Response {
        //
    }

    /**
     * @Route(name="project.network.update.post",
     *     path="/project/{projectId}/network/{networkId}/update",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $networkId
     * @return AjaxResponse
     */
    public function postUpdate(
        Request $request,
        Entity\User $user,
        string $projectId,
        string $networkId
    ) : AjaxResponse {
        //
    }

    /**
     * @Route(name="project.network.delete.post",
     *     path="/project/{projectId}/network/{networkId}/delete",
     *     methods={"POST"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $networkId
     * @return Response
     */
    public function postDelete(
        Entity\User $user,
        string $projectId,
        string $networkId
    ) : Response {
        //
    }
}
