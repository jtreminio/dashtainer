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

    public function __construct(EntityManagerInterface $em)
    {
        $this->em          = $em;
        $this->projectRepo = $em->getRepository('DashtainerBundle:Project');
    }

    /**
     * @Route(name="project.index.get",
     *     path="/project",
     *     methods={"GET"}
     * )
     */
    public function indexGetAction()
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
     * @return Response
     */
    public function createPostAction(Request $request, Entity\User $user) : Response
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
            'data' => $this->generateUrl('project.manage.get', [
                'projectId'   => $project->getId(),
                'projectSlug' => $project->getSlug(),
            ]),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.manage.get",
     *     path="/project/manage/{projectId}/{projectSlug}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $projectSlug
     * @return Response
     */
    public function manageGetAction(
        Entity\User $user,
        string $projectId,
        string $projectSlug
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/manage.html.twig', [
            'project' => $project,
        ]);
    }
}
