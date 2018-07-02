<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Form\Docker\Service;
use Dashtainer\Util;

class FormDockerServiceCreate extends Service\CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
}
