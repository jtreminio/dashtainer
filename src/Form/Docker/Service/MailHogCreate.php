<?php

namespace Dashtainer\Form\Docker\Service;

use Dashtainer\Util;
use Dashtainer\Validator\Constraints as DashAssert;

class MailHogCreate extends CreateAbstract implements Util\HydratorInterface
{
    use Util\HydratorTrait;
    use DashAssert\CustomFileTrait;
}
