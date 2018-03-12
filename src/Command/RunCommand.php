<?php

namespace Dashtainer\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class RunCommand extends CommandAbstract
{
    protected function configure()
    {
        $this->setName('dashtainer:run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return 0;
    }
}
