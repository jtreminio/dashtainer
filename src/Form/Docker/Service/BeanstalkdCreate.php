<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

use Symfony\Component\Validator\Constraints as Assert;

class BeanstalkdCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\CustomFileTrait;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice({"docker", "local"})
     */
    public $datastore;
}
