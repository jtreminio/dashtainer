<?php

namespace Dashtainer\Controller;

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

class ProjectController extends Controller
{
    /** @var Domain\DockerProject */
    protected $dockerProjectDomain;

    /** @var Repository\DockerProjectRepository */
    protected $dProjectRepo;

    /** @var Repository\DockerServiceCategoryRepository */
    protected $dServiceCatRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\DockerProject $dockerProjectDomain,
        Repository\DockerProjectRepository $dProjectRepo,
        Repository\DockerServiceCategoryRepository $dServiceCatRepo,
        Validator\Validator $validator
    ) {
        $this->dockerProjectDomain = $dockerProjectDomain;

        $this->dProjectRepo    = $dProjectRepo;
        $this->dServiceCatRepo = $dServiceCatRepo;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.index.get",
     *     path="/project",
     *     methods={"GET"}
     * )
     * @return Response
     */
    public function getIndex() : Response
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
    public function postCreate(Request $request, Entity\User $user) : AjaxResponse
    {
        $form = new Form\DockerProjectCreateUpdateForm();
        $form->fromArray($request->request->all());

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $project = $this->dockerProjectDomain->createProjectFromForm($form, $user);

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
    public function getView(
        Entity\User $user,
        string $projectId
    ) : Response {
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/view.html.twig', [
            'project'           => $project,
            'serviceCategories' => $this->dServiceCatRepo->findAll(),
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
    public function getUpdate(
        Entity\User $user,
        string $projectId
    ) : Response {
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/update.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route(name="project.update.post",
     *     path="/project/{projectId}/update",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @return AjaxResponse
     */
    public function postUpdate(
        Request $request,
        Entity\User $user,
        string $projectId
    ) : AjaxResponse {
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        $form = new Form\DockerProjectCreateUpdateForm();
        $form->fromArray($project->toArray());
        $form->fromArray($request->request->all());

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $project->fromArray($form->toArray());

        $this->dProjectRepo->save($project);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.view.get', [
                'projectId' => $project->getId(),
            ]),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.delete.post",
     *     path="/project/{projectId}/delete",
     *     methods={"POST"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function postDelete(
        Entity\User $user,
        string $projectId
    ) : Response {
        $project = $this->dProjectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if ($project) {
            $this->dockerProjectDomain->delete($project);
        }

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.index.get'),
        ], AjaxResponse::HTTP_OK);
    }
}
