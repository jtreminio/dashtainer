<?php

namespace Dashtainer\Validator\Constraints;

use Symfony\Component\Validator\Constraints as Assert;

trait DatastoreTrait
{
    /**
     * @Assert\NotBlank()
     * @Assert\Choice({"docker", "local"})
     */
    public $datastore;
}
