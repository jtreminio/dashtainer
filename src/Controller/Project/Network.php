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

class Network extends Controller
{
    /** @var Domain\Docker\Network */
    protected $dNetworkDomain;

    /** @var Domain\Docker\Service */
    protected $dServiceDomain;

    /** @var Repository\Docker\Network */
    protected $dNetworkRepo;

    /** @var Repository\Docker\Project */
    protected $dProjectRepo;

    /** @var Repository\Docker\Service */
    protected $dServiceRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\Docker\Network $dNetworkDomain,
        Domain\Docker\Service $dServiceDomain,
        Repository\Docker\Network $dNetworkRepo,
        Repository\Docker\Project $dProjectRepo,
        Repository\Docker\Service $dServiceRepo,
        Validator\Validator $validator
    ) {
        $this->dNetworkDomain = $dNetworkDomain;
        $this->dServiceDomain = $dServiceDomain;

        $this->dNetworkRepo = $dNetworkRepo;
        $this->dProjectRepo = $dProjectRepo;
        $this->dServiceRepo = $dServiceRepo;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.network.index.get",
     *     path="/project/{projectId}/network",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function getIndex(
        Entity\User $user,
        string $projectId
    ) : Response {
    }

    /**
     * @Route(name="project.network.create.get",
     *     path="/project/{projectId}/network/create",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function getCreate(
        Entity\User $user,
        string $projectId
    ) : Response {
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $networkName = $this->dNetworkDomain->generateName($project);

        return $this->render('@Dashtainer/project/network/create.html.twig', [
            'user'        => $user,
            'project'     => $project,
            'networkName' => $networkName,
        ]);
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form = new Form\Docker\NetworkCreateUpdate();
        $form->fromArray($request->request->all());

        $form->network_name_used = $this->dNetworkRepo->findOneBy([
            'project' => $project,
            'name'    => $form->name,
        ]);

        $form->project = $project;

        $form->services_non_existant = $this->dServiceDomain->validateByName(
            $project,
            $form->services
        );

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $network = $this->dNetworkDomain->createFromForm($form);

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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$network = $this->dNetworkRepo->findByProject($project, $networkId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        return $this->render('@Dashtainer/project/network/view.html.twig', [
            'project' => $project,
            'network' => $network,
        ]);
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$network = $this->dNetworkRepo->findByProject($project, $networkId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $notServices = $this->dServiceRepo->findByNotNetwork($network);

        return $this->render('@Dashtainer/project/network/update.html.twig', [
            'project'     => $project,
            'network'     => $network,
            'notServices' => $notServices,
        ]);
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        if (!$network = $this->dNetworkRepo->findByProject($project, $networkId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form = new Form\Docker\NetworkCreateUpdate();
        $form->fromArray($request->request->all());

        $existingNetworkNameUsed = $this->dNetworkRepo->findOneBy([
            'project' => $project,
            'name'    => $form->name,
        ]);

        if ($network->getId() !== $existingNetworkNameUsed->getId()) {
            $form->network_name_used = true;
        }

        $form->project = $project;

        $form->services_non_existant = $this->dServiceDomain->validateByName(
            $project,
            $form->services
        );

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $network = $this->dNetworkDomain->updateFromForm($form);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.network.view.get', [
                'projectId' => $form->project->getId(),
                'networkId' => $network->getId(),
            ]),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.network.delete.post",
     *     path="/project/{projectId}/network/{networkId}/delete",
     *     methods={"POST"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $networkId
     * @return AjaxResponse
     */
    public function postDelete(
        Entity\User $user,
        string $projectId,
        string $networkId
    ) : AjaxResponse {
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        if (!$network = $this->dNetworkRepo->findByProject($project, $networkId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        $this->dNetworkDomain->delete($network);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.index.get'),
        ], AjaxResponse::HTTP_OK);
    }
}
