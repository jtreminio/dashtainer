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

class ServiceController extends Controller
{
    /** @var Domain\DockerService */
    protected $dockerServiceDomain;

    /** @var Repository\DockerProjectRepository */
    protected $dProjectRepo;

    /** @var Repository\DockerServiceRepository */
    protected $dServiceRepo;

    /** @var Repository\DockerServiceTypeRepository */
    protected $dServiceTypeRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\DockerService $dockerServiceDomain,
        Repository\DockerProjectRepository $dProjectRepo,
        Repository\DockerServiceRepository $dServiceRepo,
        Repository\DockerServiceTypeRepository $dServiceTypeRepo,
        Validator\Validator $validator
    ) {
        $this->dockerServiceDomain = $dockerServiceDomain;

        $this->dProjectRepo     = $dProjectRepo;
        $this->dServiceRepo     = $dServiceRepo;
        $this->dServiceTypeRepo = $dServiceTypeRepo;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.service.create.post",
     *     path="/project/{projectId}/service/create",
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
        $form = new Form\DockerServiceCreateForm();
        $form->fromArray($request->request->all());

        $form->project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        $form->service_type = $this->dServiceTypeRepo->findOneBy([
            'id'        => $form->service_type,
            'is_public' => true,
        ]);

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $service = $this->dockerServiceDomain->createServiceFromForm($form);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.service.view.get', [
                'projectId' => $form->project->getId(),
                'serviceId' => $service->getId(),
            ]),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.service.view.get",
     *     path="/project/{projectId}/service/{serviceId}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $serviceId
     * @return Response
     */
    public function getView(
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : Response {
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user,
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        $service = $this->dServiceRepo->findOneBy([
            'id'      => $serviceId,
            'project' => $project,
        ]);

        if (!$service) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        $serviceType = $service->getServiceType();
        $template    = sprintf('@Dashtainer/project/service/type/%s.html.twig',
            strtolower($serviceType->getName())
        );

        return $this->render($template, [
            'service' => $service,
            'project' => $project,
        ]);
    }

    /**
     * @Route(name="project.service.update.get",
     *     path="/project/{projectId}/service/{serviceId}/update",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $serviceId
     * @return Response
     */
    public function getUpdate(
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : Response {
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user,
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        $service = $this->dServiceRepo->findOneBy([
            'id'      => $serviceId,
            'project' => $project,
        ]);

        if (!$service) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        // todo implement
        return $this->render('@Dashtainer/project/service/view.html.twig', [
            'service' => $service,
            'project' => $project,
        ]);
    }

    /**
     * @Route(name="project.service.delete.get",
     *     path="/project/{projectId}/service/{serviceId}/delete",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $serviceId
     * @return Response
     */
    public function getDelete(
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : Response {
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user,
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        $service = $this->dServiceRepo->findOneBy([
            'id'      => $serviceId,
            'project' => $project,
        ]);

        if (!$service) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        // todo implement
        return $this->render('@Dashtainer/project/service/view.html.twig', [
            'service' => $service,
            'project' => $project,
        ]);
    }
}
