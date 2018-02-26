<?php

namespace DashtainerBundle\Controller;

use DashtainerBundle\Entity;
use DashtainerBundle\Form;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
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

        $response = new Response();

        if (!$validator->isValid()) {
            $response->setContent('bad form!');

            return $response;
        }

        $project = new Entity\Project();
        $project->fromArray($form->toArray());
        $project->setUser($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($project);
        $em->flush();

        $response->setContent('good form!');

        return $response;
    }
    /**
     * @Route(name="project.view.get",
     *     path="/project/view/{projectId}/{projectSlug}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $projectSlug
     * @return Response
     */
    public function viewGetAction(
        Entity\User $user,
        string $projectId,
        string $projectSlug
    ) : Response {
        $em = $this->getDoctrine()->getRepository('DashtainerBundle:Project');

        if (!$project = $em->find($projectId)) {
            $response = new Response();

            $response->setContent('project not found');

            return $response;
        }

        if ($project->getUser() !== $user) {
            $response = new Response();

            $response->setContent('project does not belong to this user');

            return $response;
        }

        return $this->render('@Dashtainer/project/view.html.twig', [
            'project' => $project,
        ]);
    }
}
