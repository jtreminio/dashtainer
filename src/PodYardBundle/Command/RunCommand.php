<?php

namespace PodYardBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class RunCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('podyard:run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getContainer()->get('kernel');

        $baseDir    = $kernel->getRootDir() . '/..';
        $outputDir  = $baseDir . '/docker-generated';
        $configFile = $baseDir . '/var/podyard-config.yml';

        exec(sprintf('rm -rf %s', escapeshellarg($outputDir)));
        exec(sprintf('mkdir %s', escapeshellarg($outputDir)));

        $manager = $this->getContainer()->get('podyard.docker.manager');
        $manager->setPodYardConfig(Yaml::parseFile($configFile))
            ->generateArchive($outputDir);

        file_put_contents(
            "{$outputDir}/docker-compose.yml",
            $manager->getDockerConfigYaml()
        );

        return 0;
    }
}
