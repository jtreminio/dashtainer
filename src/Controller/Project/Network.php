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
}
