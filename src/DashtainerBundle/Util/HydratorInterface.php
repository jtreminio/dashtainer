<?php

namespace DashtainerBundle\Util;

interface HydratorInterface
{
    public function fromArray(array $data);

    public function toArray() : array;
}
