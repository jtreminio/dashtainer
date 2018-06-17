<?php

namespace Dashtainer\Controller;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Response\AjaxResponse;
use Dashtainer\Validator;

use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Project extends Controller
{
    /** @var Domain\Docker\Export */
    protected $dExportDomain;

    /** @var Domain\Docker\Project */
    protected $dProjectDomain;

    /** @var Repository\Docker\Network */
    protected $dNetworkRepo;

    /** @var Repository\Docker\Project */
    protected $dProjectRepo;

    /** @var Repository\Docker\Service */
    protected $dServiceRepo;

    /** @var Repository\Docker\ServiceCategory */
    protected $dServiceCatRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\Docker\Export $dExportDomain,
        Domain\Docker\Project $dProjectDomain,
        Repository\Docker\Network $dNetworkRepo,
        Repository\Docker\Project $dProjectRepo,
        Repository\Docker\Service $dServiceRepo,
        Repository\Docker\ServiceCategory $dServiceCatRepo,
        Validator\Validator $validator
    ) {
        $this->dExportDomain  = $dExportDomain;
        $this->dProjectDomain = $dProjectDomain;

        $this->dNetworkRepo    = $dNetworkRepo;
        $this->dProjectRepo    = $dProjectRepo;
        $this->dServiceRepo    = $dServiceRepo;
        $this->dServiceCatRepo = $dServiceCatRepo;

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
            'projectList' => $this->dProjectDomain->getList($user),
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

        $form->project_name_used = $this->dProjectRepo->findOneBy([
            'user' => $user,
            'name' => $form->name,
        ]);

        $this->validator->setSource($form);

        if (!$this->validator->isValid()) {
            return new AjaxResponse([
                'type'   => AjaxResponse::AJAX_ERROR,
                'errors' => $this->validator->getErrors(true),
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $project = $this->dProjectDomain->createProjectFromForm($form, $user);

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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $services = $this->dServiceRepo->findAllPublicByProject($project);

        $servicesCategorized = [];
        foreach ($services as $service) {
            $servicesCategorized[$service->getType()->getCategory()->getId()] []= $service;
        }

        return $this->render('@Dashtainer/project/view.html.twig', [
            'project'             => $project,
            'serviceCategories'   => $this->dServiceCatRepo->findAll(),
            'servicesCategorized' => $servicesCategorized,
            'services'            => $services,
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
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
        $project = $this->dProjectRepo->findByUser($user, $projectId);

        $form = new Form\Docker\ProjectCreateUpdate();
        $form->fromArray($project->toArray());
        $form->fromArray($request->request->all());

        $existingProject = $this->dProjectRepo->findOneBy([
            'user' => $user,
            'name' => $form->name,
        ]);

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
        if ($project = $this->dProjectRepo->findByUser($user, $projectId)) {
            $this->dProjectDomain->delete($project);
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        $response = new StreamedResponse(function() use ($project, $traefik) {
            $opt = [
                'content_type' => 'application/octet-stream'
            ];

            $zip = new ZipStream('dashtainer.zip', $opt);

            $this->dExportDomain->setArchiver($zip);
            $this->dExportDomain->download($project, $traefik === 'traefik');

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
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return $this->render('@Dashtainer/project/not-found.html.twig');
        }

        return $this->render('@Dashtainer/project/dump.html.twig', [
            'project' => $project,
            'dump'    => $this->dExportDomain->dump($project),
        ]);
    }
}
