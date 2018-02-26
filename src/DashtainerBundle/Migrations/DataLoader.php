<?php

namespace DashtainerBundle\Migrations;

use Symfony\Component\Serializer\Encoder\DecoderInterface;

class DataLoader
{
    /** @var string */
    protected $baseDir;

    /** @var DecoderInterface */
    protected $dataDecoder;

    /** @var string */
    protected $env;

    public function __construct(
        DecoderInterface $dataDecoder,
        string $env
    ) {
        $this->dataDecoder = $dataDecoder;
        $this->env         = $env;
    }

    public function setBaseDir(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function getData(string $filename) : array
    {
        $rows = [];

        // data-{env}.yml
        if (file_exists("{$this->baseDir}/{$filename}-{$this->env}.yml")) {
            $data = file_get_contents("{$this->baseDir}/{$filename}-{$this->env}.yml");
            $rows = empty($data)
                ? $rows
                : array_merge_recursive($rows, $this->dataDecoder->decode($data, 'yaml'));
        }

        // data.yml
        if (file_exists("{$this->baseDir}/{$filename}.yml")) {
            $data = file_get_contents("{$this->baseDir}/{$filename}.yml");
            $rows = empty($data)
                ? $rows
                : array_merge_recursive($rows, $this->dataDecoder->decode($data, 'yaml'));
        }

        return $rows;
    }
}
