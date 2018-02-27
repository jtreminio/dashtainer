<?php

namespace DashtainerBundle\Controller;

use DashtainerBundle\Entity;
use DashtainerBundle\Form;
use DashtainerBundle\Repository;
use DashtainerBundle\Response\AjaxResponse;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var Repository\ProjectRepository */
    protected $projectRepo;

    /** @var Repository\ServiceCategoryRepository */
    protected $serviceCatRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        $this->projectRepo    = $em->getRepository('DashtainerBundle:Project');
        $this->serviceCatRepo = $em->getRepository('DashtainerBundle:ServiceCategory');
    }

    /**
     * @Route(name="project.index.get",
     *     path="/project",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @return Response
     */
    public function getIndexAction(Entity\User $user) : Response
    {
        return $this->render('@Dashtainer/project/index.html.twig', [
        ]);
    }

    /**
     * @Route(name="project.create.post",
     *     path="/project/create",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @return AjaxResponse
     */
    public function postCreateAction(Request $request, Entity\User $user) : AjaxResponse
    {
        $form = new Form\ProjectCreateForm();
        $form->fromArray($request->request->all());

        $validator = $this->get('dashtainer.domain.validator');
        $validator->setSource($form);

        if (!$validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $project = new Entity\Project();
        $project->fromArray($form->toArray());
        $project->setUser($user);

        $this->em->persist($project);
        $this->em->flush();

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.view.get', [
                'projectId' => $project->getId(),
            ]),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.view.get",
     *     path="/project/{projectId}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function getViewAction(
        Entity\User $user,
        string $projectId
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/view.html.twig', [
            'project'           => $project,
            'serviceCategories' => $this->serviceCatRepo->findAll(),
        ]);
    }

    /**
     * @Route(name="project.update.get",
     *     path="/project/{projectId}/update",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function getUpdateAction(
        Entity\User $user,
        string $projectId
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        // todo implement
        return $this->render('@Dashtainer/project/view.html.twig', [
            'project'           => $project,
            'serviceCategories' => $this->serviceCatRepo->findAll(),
        ]);
    }

    /**
     * @Route(name="project.delete.get",
     *     path="/project/{projectId}/delete",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function getDeleteAction(
        Entity\User $user,
        string $projectId
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        // todo implement
        return $this->render('@Dashtainer/project/view.html.twig', [
            'project'           => $project,
            'serviceCategories' => $this->serviceCatRepo->findAll(),
        ]);
    }
}
