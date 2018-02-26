<?php

namespace DashtainerBundle\Entity;

interface EntityBaseInterface
{
    public function getId();

    public function getCreatedAt() : ?\DateTime;

    public function setCreatedAt($created_at);

    public function getUpdatedAt() : ?\DateTime;

    public function setUpdatedAt($updated_at);
}
