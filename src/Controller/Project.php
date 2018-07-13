<?php

namespace Dashtainer\Controller;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Response\AjaxResponse;
use Dashtainer\Validator;

use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Project extends Controller
{
    /** @var Domain\Docker\Export */
    protected $exportDomain;

    /** @var Domain\Docker\Project */
    protected $projectDomain;

    /** @var Domain\Docker\ServiceCategory */
    protected $serviceCatDomain;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\Docker\Export $exportDomain,
        Domain\Docker\Project $projectDomain,
        Domain\Docker\ServiceCategory $serviceCatDomain,
        Validator\Validator $validator
    ) {
        $this->exportDomain     = $exportDomain;
        $this->projectDomain    = $projectDomain;
        $this->serviceCatDomain = $serviceCatDomain;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.index.get",
     *     path="/project",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @return Response
     */
    public function getIndex(Entity\User $user) : Response
    {
        return $this->render('@Dashtainer/project/index.html.twig', [
            'user'        => $user,
            'projectList' => $this->projectDomain->getList($user),
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
        $form = new Form\Docker\ProjectCreateUpdate();
        $form->fromArray($request->request->all());

        $form->project_name_used = $this->projectDomain->getByUserAndName($user, $form->name);
        $form->user = $user;

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $project = $this->projectDomain->create($form);

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
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/view.html.twig', [
            'project'             => $project,
            'serviceCategories'   => $this->serviceCatDomain->getAll(),
            'servicesCategorized' => $this->serviceCatDomain->getPublicServices($project),
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
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
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
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => $this->generateUrl('project.index.get'),
            ], AjaxResponse::HTTP_OK);
        }

        $form = new Form\Docker\ProjectCreateUpdate();
        $form->fromArray($project->toArray());
        $form->fromArray($request->request->all());
        $form->user = $user;

        $existingProject = $this->projectDomain->getByUserAndName($user, $form->name);

        if ($existingProject && $existingProject->getId() !== $project->getId()) {
            $form->project_name_used = true;
        }

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $project->fromArray($form->toArray());

        $this->projectDomain->update($project);

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
        if ($project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            $this->projectDomain->delete($project);
        }

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_REDIRECT,
            'data' => $this->generateUrl('project.index.get'),
        ], AjaxResponse::HTTP_OK);
    }

    /**
     * @Route(name="project.download.get",
     *     path="/project/{projectId}/download",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function getDownload(
        Entity\User $user,
        string $projectId
    ) : Response {
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/download.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route(name="project.download.file.get",
     *     path="/project/{projectId}/download/{traefik}",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @param string      $traefik
     * @return StreamedResponse
     */
    public function getDownloadArchive(
        Entity\User $user,
        string $projectId,
        string $traefik
    ) : Response {
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $response = new StreamedResponse(function() use ($project, $traefik) {
            $opt = [
                'content_type' => 'application/octet-stream'
            ];

            $zip = new ZipStream('dashtainer.zip', $opt);

            $this->exportDomain->setArchiver($zip);
            $this->exportDomain->download($project, $traefik === 'traefik');

            $zip->finish();
        });

        return $response;
    }

    /**
     * @Route(name="project.download.dump.get",
     *     path="/project/{projectId}/dump",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function getDump(
        Entity\User $user,
        string $projectId
    ) : Response {
        if (!$project = $this->projectDomain->getByUserAndId($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/dump.html.twig', [
            'project' => $project,
            'dump'    => $this->exportDomain->dump($project),
        ]);
    }
}
