<?php

namespace Dashtainer\Controller\Project;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Repository;
use Dashtainer\Response\AjaxResponse;
use Dashtainer\Validator;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Service extends Controller
{
    /** @var Domain\Docker\Service */
    protected $dServiceDomain;

    /** @var Domain\Docker\WorkerBag */
    protected $workerBag;

    /** @var Domain\Docker\Project */
    protected $dProjectDomain;

    /** @var Repository\Docker\Service */
    protected $dServiceRepo;

    /** @var Repository\Docker\ServiceCategory */
    protected $dServiceCatRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\Docker\Project $dProjectDomain,
        Domain\Docker\Service $dServiceDomain,
        Domain\Docker\WorkerBag $workerBag,
        Repository\Docker\Service $dServiceRepo,
        Repository\Docker\ServiceCategory $dServiceCatRepo,
        Validator\Validator $validator
    ) {
        $this->dProjectDomain = $dProjectDomain;
        $this->dServiceDomain = $dServiceDomain;
        $this->workerBag      = $workerBag;

        $this->dServiceRepo     = $dServiceRepo;
        $this->dServiceCatRepo  = $dServiceCatRepo;

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
        if (!$project = $this->dProjectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$worker = $this->workerBag->getWorkerFromType($serviceTypeSlug)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $worker->setVersion($version);

        $template = sprintf('@Dashtainer/project/service/%s/create.html.twig',
            strtolower($serviceTypeSlug)
        );

        return $this->render($template, array_merge([
            'user'              => $user,
            'project'           => $project,
            'serviceCategories' => $this->dServiceCatRepo->findAll(),
            'serviceType'       => $worker->getServiceType(),

        ], $worker->getCreateParams($project)));
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
        if (!$project = $this->dProjectDomain->getByUserAndId($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        if (!$worker = $this->workerBag->getWorkerFromType($serviceTypeSlug)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form = $worker->getCreateForm();
        $form->fromArray($request->request->all());

        $worker->setVersion($form->version ?? null);

        $form->project = $project;
        $form->type    = $worker->getServiceType();

        $form->service_name_used = $this->dServiceRepo->findOneBy([
            'project' => $project,
            'name'    => $form->name,
        ]);

        $form->ports_used = $this->dServiceDomain->getUsedPublishedPorts($project);

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $worker->create($form);

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
        if (!$project = $this->dProjectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$service = $this->dServiceRepo->findByProject($project, $serviceId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();

        $worker = $this->workerBag->getWorkerFromType($serviceType->getSlug());
        $worker->setVersion($service->getVersion());

        $template = sprintf('@Dashtainer/project/service/%s/view.html.twig',
            strtolower($serviceType->getSlug())
        );

        return $this->render($template, array_merge([
            'service'           => $service,
            'project'           => $project,
            'serviceCategories' => $this->dServiceCatRepo->findAll(),
        ], $worker->getViewParams($service)));
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
        if (!$project = $this->dProjectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$service = $this->dServiceRepo->findByProject($project, $serviceId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();

        $worker = $this->workerBag->getWorkerFromType($serviceType->getSlug());
        $worker->setVersion($service->getVersion());

        $template = sprintf('@Dashtainer/project/service/%s/update.html.twig',
            strtolower($serviceType->getSlug())
        );

        return $this->render($template, array_merge([
            'service' => $service,
            'project' => $project,
        ], $worker->getViewParams($service)));
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
        if (!$project = $this->dProjectDomain->getByUserAndId($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        if (!$service = $this->dServiceRepo->findByProject($project, $serviceId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $serviceType = $service->getType();

        $worker = $this->workerBag->getWorkerFromType($serviceType->getSlug());
        $worker->setVersion($form->version ?? null);

        $form = $worker->getCreateForm();
        $form->fromArray($request->request->all());
        $form->project = $project;
        $form->type    = $serviceType;
        $form->name    = $service->getName();

        $form->ports_used = $this->dServiceDomain->getUsedPublishedPorts($project, $service);

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $worker->update($service, $form);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.service.view.get', [
                'projectId' => $form->project->getId(),
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
        if (!$project = $this->dProjectDomain->getByUserAndId($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        if (!$service = $this->dServiceRepo->findByProject($project, $serviceId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        $serviceType = $service->getType();

        $worker = $this->workerBag->getWorkerFromType($serviceType->getSlug());

        $worker->delete($service);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.index.get'),
        ], AjaxResponse::HTTP_OK);
    }
}
