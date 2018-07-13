<?php

namespace Dashtainer\Controller\Project;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Response\AjaxResponse;
use Dashtainer\Validator;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Service extends Controller
{
    /** @var Domain\Docker\Project */
    protected $projectDomain;

    /** @var Domain\Docker\Service */
    protected $serviceDomain;

    /** @var Domain\Docker\ServiceCategory */
    protected $serviceCatDomain;

    /** @var Domain\Docker\WorkerHandler */
    protected $workerHandler;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\Docker\Project $projectDomain,
        Domain\Docker\ServiceCategory $serviceCatDomain,
        Domain\Docker\Service $serviceDomain,
        Domain\Docker\WorkerHandler $workerHandler,
        Validator\Validator $validator
    ) {
        $this->projectDomain    = $projectDomain;
        $this->serviceDomain    = $serviceDomain;
        $this->serviceCatDomain = $serviceCatDomain;
        $this->workerHandler    = $workerHandler;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.service.create.get",
     *     path="/project/{projectId}/service/create/{serviceTypeSlug}/{version}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $serviceTypeSlug
     * @param string|null $version
     * @return Response
     */
    public function getCreate(
        Entity\User $user,
        string $projectId,
        string $serviceTypeSlug,
        string $version = null
    ) : Response {
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$this->workerHandler->setWorkerFromServiceType($serviceTypeSlug)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $service = new Entity\Docker\Service();
        $service->setProject($project)
            ->setVersion($version);

        $this->workerHandler->setService($service);

        $template = sprintf('@Dashtainer/project/service/%s/create.html.twig',
            strtolower($serviceTypeSlug)
        );

        return $this->render($template, array_merge([
            'user'              => $user,
            'serviceCategories' => $this->serviceCatDomain->getAll(),
        ], $this->workerHandler->getCreateParams()));
    }

    /**
     * @Route(name="project.service.create.post",
     *     path="/project/{projectId}/service/create/{serviceTypeSlug}",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $serviceTypeSlug
     * @return AjaxResponse
     */
    public function postCreate(
        Request $request,
        Entity\User $user,
        string $projectId,
        string $serviceTypeSlug
    ) : AjaxResponse {
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        if (!$this->workerHandler->setWorkerFromServiceType($serviceTypeSlug)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $service = new Entity\Docker\Service();
        $service->setProject($project);

        $this->workerHandler->setService($service);

        $form = $this->workerHandler->getForm();
        $form->fromArray($request->request->all());

        $form->service_name_used = $this->serviceDomain->getByName($project, $form->name);
        $form->ports_used        = $this->serviceDomain->getUsedPublishedPorts($project);

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $this->workerHandler->create();

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.view.get', [
                'projectId' => $project->getId(),
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
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$service = $this->serviceDomain->getByProjectAndId($project, $serviceId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();

        $this->workerHandler->setService($service);

        $template = sprintf('@Dashtainer/project/service/%s/view.html.twig',
            strtolower($serviceType->getSlug())
        );

        return $this->render($template, array_merge([
            'serviceCategories' => $this->serviceCatDomain->getAll(),
        ], $this->workerHandler->getViewParams()));
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
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$service = $this->serviceDomain->getByProjectAndId($project, $serviceId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();

        $this->workerHandler->setService($service);

        $template = sprintf('@Dashtainer/project/service/%s/update.html.twig',
            strtolower($serviceType->getSlug())
        );

        return $this->render($template, array_merge([
            'user' => $user,
        ], $this->workerHandler->getViewParams()));
    }

    /**
     * @Route(name="project.service.update.post",
     *     path="/project/{projectId}/service/{serviceId}/update",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $serviceId
     * @return AjaxResponse
     */
    public function postUpdate(
        Request $request,
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : AjaxResponse {
        ;
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        if (!$service = $this->serviceDomain->getByProjectAndId($project, $serviceId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $this->workerHandler->setService($service);

        $form = $this->workerHandler->getForm();
        $form->fromArray($request->request->all());

        $form->name       = $service->getName();
        $form->ports_used = $this->serviceDomain->getUsedPublishedPorts($project, $service);

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $this->workerHandler->update();

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.service.view.get', [
                'projectId' => $project->getId(),
                'serviceId' => $service->getId(),
            ]),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.service.delete.post",
     *     path="/project/{projectId}/service/{serviceId}/delete",
     *     methods={"POST"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $serviceId
     * @return AjaxResponse
     */
    public function postDelete(
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : AjaxResponse {
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        if (!$service = $this->serviceDomain->getByProjectAndId($project, $serviceId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        $this->workerHandler->setService($service);
        $this->workerHandler->delete();

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.index.get'),
        ], AjaxResponse::HTTP_OK);
    }
}
