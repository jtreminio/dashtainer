<?php

namespace PodYardBundle\Util;

interface HydratorInterface
{
    public function fromArray(array $data);

    public function toArray() : array;
}
