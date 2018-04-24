<?php

namespace Dashtainer\Tests\Mock;

use Dashtainer\Form\Docker\Service;
use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

class FormDockerServiceCreate extends Service\CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\DatastoreTrait;
    use DashAssert\ProjectFilesTrait;
    use DashAssert\UserFileTrait;
}
