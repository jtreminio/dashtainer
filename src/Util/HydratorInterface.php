<?php

namespace Dashtainer\Util;

interface HydratorInterface
{
    public function fromArray(array $data);

    public function toArray() : array;
}
