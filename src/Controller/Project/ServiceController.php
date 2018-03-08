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
    protected $dServiceDomain;

    /** @var Repository\DockerProjectRepository */
    protected $dProjectRepo;

    /** @var Repository\DockerServiceCategoryRepository */
    protected $dServiceCatRepo;

    /** @var Repository\DockerServiceRepository */
    protected $dServiceRepo;

    /** @var Repository\DockerServiceTypeRepository */
    protected $dServiceTypeRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\DockerService $dServiceDomain,
        Repository\DockerServiceCategoryRepository $dServiceCatRepo,
        Repository\DockerProjectRepository $dProjectRepo,
        Repository\DockerServiceRepository $dServiceRepo,
        Repository\DockerServiceTypeRepository $dServiceTypeRepo,
        Validator\Validator $validator
    ) {
        $this->dServiceDomain = $dServiceDomain;

        $this->dProjectRepo     = $dProjectRepo;
        $this->dServiceCatRepo  = $dServiceCatRepo;
        $this->dServiceRepo     = $dServiceRepo;
        $this->dServiceTypeRepo = $dServiceTypeRepo;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.service.index.get",
     *     path="/project/{projectId}/service",
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/service/index.html.twig', [
            'user'              => $user,
            'project'           => $project,
            'serviceCategories' => $this->dServiceCatRepo->findAll(),
        ]);
    }

    /**
     * @Route(name="project.service.block-add-file.get",
     *     path="/project/{projectId}/service/block-add-file",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getBlockAddFile(
    ) : AjaxResponse {
        $uniqid = uniqid();

        $id   = "additional_file-{$uniqid}";
        $name = "additional_file[{$uniqid}]";

        $blockTab = $this->render('@Dashtainer/project/service/_block_tab_file.html.twig', [
            'id'             => $id,
            'name'           => $name,
            'errorContainer' => 'additional_file',
        ]);

        $blockContent = $this->render('@Dashtainer/project/service/_block_content_file.html.twig', [
            'id'             => $id,
            'name'           => $name,
            'errorContainer' => 'additional_file',
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => [
                'tab'     => $blockTab->getContent(),
                'content' => $blockContent->getContent(),
            ],
        ], AjaxResponse::HTTP_OK);
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$serviceType = $this->dServiceTypeRepo->findOneBy([
            'slug' => $serviceTypeSlug,
        ])) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $serviceName = $this->dServiceDomain->generateServiceName(
            $project,
            $serviceType,
            $version
        );

        $template = sprintf('@Dashtainer/project/service/create/%s.html.twig',
            strtolower($serviceTypeSlug)
        );

        return $this->render($template, [
            'user'        => $user,
            'project'     => $project,
            'serviceName' => $serviceName,
            'serviceType' => $serviceType,
            'version'     => $version,
        ]);
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
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        $service_type = $this->dServiceTypeRepo->findOneBy([
            'slug' => $serviceTypeSlug,
        ]);

        if (!$form = $this->dServiceDomain->getCreateForm($service_type)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form->fromArray($request->request->all());

        $form->service_name_used = $this->dServiceRepo->findOneBy([
            'project' => $project,
            'name'    => $form->name,
        ]);

        $form->project = $project;
        $form->type    = $service_type;

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $service = $this->dServiceDomain->createService($form);

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
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $service = $this->dServiceRepo->findOneBy([
            'id'      => $serviceId,
            'project' => $project,
        ]);

        if (!$service) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();
        $template    = sprintf('@Dashtainer/project/service/view/%s.html.twig',
            strtolower($serviceType->getName())
        );

        $params = $this->dServiceDomain->getViewParams($service);

        return $this->render($template, array_merge([
            'service' => $service,
            'project' => $project,
        ], $params));
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
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $service = $this->dServiceRepo->findOneBy([
            'id'      => $serviceId,
            'project' => $project,
        ]);

        if (!$service) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();
        $template    = sprintf('@Dashtainer/project/service/update/%s.html.twig',
            strtolower($serviceType->getName())
        );

        $params = $this->dServiceDomain->getViewParams($service);

        return $this->render($template, array_merge([
            'service' => $service,
            'project' => $project,
        ], $params));
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
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $service = $this->dServiceRepo->findOneBy([
            'id'      => $serviceId,
            'project' => $project,
        ]);

        if (!$service) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $service_type = $service->getType();

        if (!$form = $this->dServiceDomain->getCreateForm($service_type)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form->fromArray($request->request->all());

        $form->name    = $service->getName();
        $form->project = $project;
        $form->type    = $service_type;

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $service = $this->dServiceDomain->updateService($service, $form);

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
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        $service = $this->dServiceRepo->findOneBy([
            'id'      => $serviceId,
            'project' => $project,
        ]);

        if (!$service) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        $this->dServiceDomain->deleteService($service);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.index.get'),
        ], AjaxResponse::HTTP_OK);
    }
}
