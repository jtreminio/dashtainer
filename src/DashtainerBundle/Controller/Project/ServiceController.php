<?php

namespace DashtainerBundle\Controller\Project;

use DashtainerBundle\Entity;
use DashtainerBundle\Form;
use DashtainerBundle\Repository;
use DashtainerBundle\Response\AjaxResponse;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var Repository\DockerProjectRepository */
    protected $projectRepo;

    /** @var Repository\DockerServiceRepository */
    protected $serviceRepo;

    /** @var Repository\DockerServiceCategoryRepository */
    protected $serviceCatRepo;

    /** @var Repository\DockerServiceTypeRepository */
    protected $serviceTypeRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        $this->projectRepo     = $em->getRepository('DockerProject');
        $this->serviceRepo     = $em->getRepository('DockerService');
        $this->serviceCatRepo  = $em->getRepository('DockerServiceCategory');
        $this->serviceTypeRepo = $em->getRepository('DockerServiceType');
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
    public function postCreateAction(
        Request $request,
        Entity\User $user,
        string $projectId
    ) : AjaxResponse {
        $form = new Form\ServiceCreateForm();
        $form->fromArray($request->request->all());

        $form->project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        $form->service_type = $this->serviceTypeRepo->findOneBy([
            'id'        => $form->service_type,
            'is_public' => true,
        ]);

        $validator = $this->get('dashtainer.domain.validator');
        $validator->setSource($form);

        if (!$validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $service = new Entity\DockerService();
        $service->fromArray($form->toArray());

        $this->em->persist($service);
        $this->em->flush();

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
    public function getViewAction(
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user,
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        $service = $this->serviceRepo->findOneBy([
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
    public function getUpdateAction(
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user,
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        $service = $this->serviceRepo->findOneBy([
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
    public function getDeleteAction(
        Entity\User $user,
        string $projectId,
        string $serviceId
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user,
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/service/not-found.html.twig');
        }

        $service = $this->serviceRepo->findOneBy([
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
