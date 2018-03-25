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

class Network extends Controller
{
    /** @var Domain\Docker\Network */
    protected $dNetworkDomain;

    /** @var Repository\Docker\Network */
    protected $dNetworkRepo;

    /** @var Repository\Docker\Project */
    protected $dProjectRepo;

    /** @var Validator\Validator */
    protected $validator;

    public function __construct(
        Domain\Docker\Network $dNetworkDomain,
        Repository\Docker\Network $dNetworkRepo,
        Repository\Docker\Project $dProjectRepo,
        Validator\Validator $validator
    ) {
        $this->dNetworkDomain = $dNetworkDomain;

        $this->dNetworkRepo = $dNetworkRepo;
        $this->dProjectRepo = $dProjectRepo;

        $this->validator = $validator;
    }

    /**
     * @Route(name="project.network.block-create.get",
     *     path="/project/{projectId}/network/block-create",
     *     methods={"GET"}
     * )
     * @param Entity\User $user
     * @param string      $projectId
     * @return            AjaxResponse
     */
    public function getBlockCreate(
        Entity\User $user,
        string $projectId
    ) : AjaxResponse {
        if (!$project = $this->dProjectRepo->findByUser($user, $projectId)) {
            return new AjaxResponse([
                'type' => AjaxResponse::AJAX_REDIRECT,
                'data' => '',
            ], AjaxResponse::HTTP_BAD_REQUEST);
        }

        $uniqid = uniqid();

        $id          = "network_new-{$uniqid}";
        $name        = "network_new[{$uniqid}]";
        $networkName = $this->dNetworkDomain->generateName($project);

        $blockTemplate = '@Dashtainer/project/network/_block_content_new_network.html.twig';
        $blockContent  = $this->render($blockTemplate, [
            'id'             => $id,
            'name'           => $name,
            'errorContainer' => 'network_new',
            'networkName'    => $networkName,
        ]);

        return new AjaxResponse([
            'type' => AjaxResponse::AJAX_SUCCESS,
            'data' => [
                'content' => $blockContent->getContent(),
            ],
        ], AjaxResponse::HTTP_OK);
    }
}
