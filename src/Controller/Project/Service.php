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
    /** @var Domain\Docker\Network */
    protected $dNetworkDomain;

    /** @var Domain\Docker\Secret */
    protected $dSecretDomain;

    /** @var Domain\Docker\Service */
    protected $dServiceDomain;

    /** @var Domain\Docker\ServiceManager */
    protected $dServiceManager;

    /** @var Repository\Docker\Project */
    protected $dProjectRepo;

    /** @var Repository\Docker\Service */
    protected $dServiceRepo;

    /** @var Repository\Docker\ServiceCategory */
    protected $dServiceCatRepo;

    /** @var Repository\Docker\ServiceType */
    protected $dServiceTypeRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\Docker\Network $dNetworkDomain,
        Domain\Docker\Secret $dSecretDomain,
        Domain\Docker\Service $dServiceDomain,
        Domain\Docker\ServiceManager $dServiceManager,
        Repository\Docker\Project $dProjectRepo,
        Repository\Docker\Service $dServiceRepo,
        Repository\Docker\ServiceCategory $dServiceCatRepo,
        Repository\Docker\ServiceType $dServiceTypeRepo,
        Validator\Validator $validator
    ) {
        $this->dNetworkDomain  = $dNetworkDomain;
        $this->dSecretDomain   = $dSecretDomain;
        $this->dServiceDomain  = $dServiceDomain;
        $this->dServiceManager = $dServiceManager;

        $this->dProjectRepo     = $dProjectRepo;
        $this->dServiceRepo     = $dServiceRepo;
        $this->dServiceCatRepo  = $dServiceCatRepo;
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
     *     path="/project/{projectId}/service/block-add-file/{highlight}",
     *     methods={"GET"}
     * )
     * @param string $highlight
     * @return AjaxResponse
     */
    public function getBlockAddFile(
        string $highlight
    ) : AjaxResponse {
        $volume = new Entity\Docker\ServiceVolume();
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
     * @Route(name="project.service.block-add-network.get",
     *     path="/project/{projectId}/service/block-add-network",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return AjaxResponse
     */
    public function getBlockAddNetwork(
        Entity\User $user,
        string $projectId
    ) : AjaxResponse {
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $network = new Entity\Docker\Network();
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
     * @Route(name="project.service.block-add-secret.get",
     *     path="/project/{projectId}/service/block-add-secret",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getBlockAddSecret() : AjaxResponse
    {
        $projectSecret = new Entity\Docker\Secret();
        $projectSecret->fromArray(['id' => uniqid()]);

        $serviceSecret = new Entity\Docker\ServiceSecret();
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
     * @Route(name="project.service.block-add-volume.get",
     *     path="/project/{projectId}/service/block-add-volume",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getBlockAddVolume() : AjaxResponse
    {
        $volume = new Entity\Docker\ServiceVolume();
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
     * @Route(name="project.service.block-add-volume-bind.get",
     *     path="/project/{projectId}/service/block-add-volume-bind",
     *     methods={"GET"}
     * )
     * @return AjaxResponse
     */
    public function getBlockAddVolumeBind() : AjaxResponse
    {
        $volume = new Entity\Docker\ServiceVolume();
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

        if (!$serviceType = $this->dServiceTypeRepo->findBySlug($serviceTypeSlug)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $worker = $this->dServiceManager->getWorkerFromType($serviceType);
        $worker->setVersion($version);

        $serviceName = $this->dServiceDomain->generateName(
            $project,
            $serviceType,
            $version
        );

        $template = sprintf('@Dashtainer/project/service/%s/create.html.twig',
            strtolower($serviceTypeSlug)
        );

        return $this->render($template, array_merge([
            'user'              => $user,
            'project'           => $project,
            'serviceCategories' => $this->dServiceCatRepo->findAll(),
            'serviceName'       => $serviceName,
            'serviceType'       => $serviceType,
            'version'           => $version,
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
        $project = $this->dProjectRepo->findByUser($user, $projectId);

        $serviceType = $this->dServiceTypeRepo->findBySlug($serviceTypeSlug);

        if (!$form = $this->dServiceDomain->getCreateForm($serviceType)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form->fromArray($request->request->all());

        $worker = $this->dServiceManager->getWorkerFromType($serviceType);
        $worker->setVersion($form->version ?? null);

        $form->service_name_used = $this->dServiceRepo->findOneBy([
            'project' => $project,
            'name'    => $form->name,
        ]);

        $form->project = $project;
        $form->type    = $serviceType;

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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$service = $this->dServiceRepo->findByProject($project, $serviceId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();
        $worker      = $this->dServiceManager->getWorkerFromType($serviceType);
        $template    = sprintf('@Dashtainer/project/service/%s/view.html.twig',
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        if (!$service = $this->dServiceRepo->findByProject($project, $serviceId)) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig', [
                'project' => $project,
            ]);
        }

        $serviceType = $service->getType();
        $worker      = $this->dServiceManager->getWorkerFromType($serviceType);
        $template    = sprintf('@Dashtainer/project/service/%s/update.html.twig',
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
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

        if (!$form = $this->dServiceDomain->getCreateForm($serviceType)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $form->fromArray($request->request->all());

        $form->name    = $service->getName();
        $form->project = $project;
        $form->type    = $serviceType;

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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
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

        $this->dServiceDomain->deleteService($service);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.index.get'),
        ], AjaxResponse::HTTP_OK);
    }
}
