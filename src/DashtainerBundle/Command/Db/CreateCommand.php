<?php

namespace DashtainerBundle\Command\Db;

use DashtainerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command\CommandAbstract
{
    protected function configure()
    {
        $this->setName('dashtainer:db:create')
            ->setDescription('Drops database and rebuilds from scratch, with migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->runConsole('doctrine:database:drop', ['--force' => true]);
        $this->runConsole('doctrine:database:create');
        $this->runConsole('doctrine:schema:create');
        $this->runConsole('doctrine:migrations:migrate', ['--no-interaction' => true]);

        $this->io->success('Database installed successfully and migrations run.');

        return 0;
    }
}
