<?php

namespace PodYardBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class CommandAbstract extends Command
{
    /** @var SymfonyStyle */
    protected $io;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function runConsole(string $command, array $options = []) : int
    {
        $options['-e'] = $this->getEnv();
        $options['-q'] = null;
        $options       = array_merge($options, ['command' => $command]);

        $this->getApplication()->setAutoExit(false);

        return $this->getApplication()->run(new ArrayInput($options));
    }

    protected function getEnv() : string
    {
        $kernel = $this->getContainer()->get('kernel');

        return strtolower($kernel->getEnvironment());
    }
}
