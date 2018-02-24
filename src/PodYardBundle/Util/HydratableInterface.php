<?php

namespace PodYardBundle\Util;

interface HydratableInterface
{
    public function fromArray(array $data);

    public function toArray() : array;
}
