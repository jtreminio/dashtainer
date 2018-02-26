<?php

namespace DashtainerBundle\Controller\Project;

use DashtainerBundle\Entity;
use DashtainerBundle\Form;
use DashtainerBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
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
     * @Route(name="project.service.create.post",
     *     path="/project/manage/{projectId}/service/create",
     *     methods={"POST"}
     * )
     * @param Request     $request
     * @param Entity\User $user
     * @param string      $projectId
     * @return Response
     */
    public function createPostAction(
        Request $request,
        Entity\User $user,
        string $projectId
    ) : Response {
        $project = $this->projectRepo->findOneBy([
            'id'   => $projectId,
            'user' => $user
        ]);

        if (!$project) {
            $response = new Response();

            $response->setContent('project not found');

            return $response;
        }

        $form = new Form\ServiceCreateForm();
        $form->fromArray($request->request->all());

        $validator = $this->get('dashtainer.domain.validator');
        $validator->setSource($form);

        $response = new Response();

        if (!$validator->isValid()) {
            $response->setContent('bad form!');

            return $response;
        }

        $service = new Entity\Service();
        $service->fromArray($form->toArray());
        $service->setProject($project);

        $this->em->persist($service);
        $this->em->flush();

        $response->setContent('good form!');

        return $response;
    }
}
