<?php

namespace Dashtainer\Command\Db;

use Dashtainer\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command\CommandAbstract
{
    protected function configure()
    {
        $this->setName('dashtainer:db:create')
            ->setDescription('Drops database and rebuilds from scratch, with migrations.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Import SQL file', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->runConsole('doctrine:database:drop', ['--force' => true]);
        $this->runConsole('doctrine:database:create');

        if ($sqlFile = $input->getOption('file')) {
            $this->runConsole('doctrine:database:import', [
                'file' => $this->getDumpFile($sqlFile, $output)
            ]);
        } else {
            $this->runConsole('doctrine:schema:create');
        }

        $this->runConsole('doctrine:migrations:migrate', ['--no-interaction' => true]);

        $this->io->success('Database installed successfully and migrations run.');

        return 0;
    }

    protected function getDumpFile(string $filePath, OutputInterface $output) : string
    {
        if (mime_content_type($filePath) === 'application/x-gzip') {
            $tmpfname = tempnam('/tmp', 'sqldump');

            exec("tar -xzOf '{$filePath}' > {$tmpfname}");

            $filePath = $tmpfname;
        }

        $output->writeln("<info>Importing {$filePath}</info>");

        return $filePath;
    }
}
