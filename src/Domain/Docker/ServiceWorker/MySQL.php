<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class MySQL extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'mysql';
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\MySQLCreate();
    }

    /**
     * @param Form\Docker\Service\MySQLCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) number_format($form->version, 1);

        $service->setImage("mysql:{$version}")
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS);

        $sec  = '/run/secrets';
        $slug = $service->getSlug();

        $service->setEnvironments([
            'MYSQL_ROOT_PASSWORD_FILE' => "{$sec}/{$slug}-mysql_root_password",
            'MYSQL_DATABASE_FILE'      => "{$sec}/{$slug}-mysql_database",
            'MYSQL_USER_FILE'          => "{$sec}/{$slug}-mysql_user",
            'MYSQL_PASSWORD_FILE'      => "{$sec}/{$slug}-mysql_password",
        ]);

        $this->serviceRepo->save($service);

        $this->createSecrets($service, $form);

        $this->addToPrivateNetworks($service, $form);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:3306"] : [];

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('bind-port')
            ->setData($portMetaData)
            ->setService($service);

        $service->addMeta($portMeta)
            ->setPorts($servicePort);

        $this->serviceRepo->save($versionMeta, $portMeta, $service);

        $configFileCnf = new Entity\Docker\ServiceVolume();
        $configFileCnf->setName('config-file.cnf')
            ->setSource("\$PWD/{$service->getSlug()}/config-file.cnf")
            ->setTarget('/etc/mysql/conf.d/config-file.cnf')
            ->setData($form->system_file['config-file.cnf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($configFileCnf);

        $this->serviceRepo->save($configFileCnf, $service);

        $this->createDatastore($service, $form, '/var/lib/mysql');

        $this->userFilesCreate($service, $form);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [
            'bindPort' => $this->getOpenBindPort($project),
        ];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        $version   = $service->getMeta('version')->getData()[0];
        $version   = (string) number_format($version, 1);
        $datastore = $service->getMeta('datastore')->getData()[0];

        $bindPortMeta = $service->getMeta('bind-port');
        $bindPort     = $bindPortMeta->getData()[0]
            ?? $this->getOpenBindPort($service->getProject());
        $portConfirm  = $bindPortMeta->getData()[0] ?? false;

        $secrets = $this->getSecrets($service);

        $mysql_root_password = $secrets['mysql_root_password']->getProjectSecret()->getContents();
        $mysql_database      = $secrets['mysql_database']->getProjectSecret()->getContents();
        $mysql_user          = $secrets['mysql_user']->getProjectSecret()->getContents();
        $mysql_password      = $secrets['mysql_password']->getProjectSecret()->getContents();

        $configFileCnf = $service->getVolume('config-file.cnf');

        $userFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        return [
            'version'             => $version,
            'datastore'           => $datastore,
            'bindPort'            => $bindPort,
            'portConfirm'         => $portConfirm,
            'mysql_root_password' => $mysql_root_password,
            'mysql_database'      => $mysql_database,
            'mysql_user'          => $mysql_user,
            'mysql_password'      => $mysql_password,
            'systemFiles'         => [
                'config-file.cnf' => $configFileCnf,
            ],
            'userFiles'           => $userFiles,
        ];
    }

    /**
     * @param Entity\Docker\Service           $service
     * @param Form\Docker\Service\MySQLCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:3306"] : [];

        $portMeta = $service->getMeta('bind-port');
        $portMeta->setData($portMetaData);

        $this->serviceRepo->save($portMeta);

        $service->setPorts($servicePort);

        $configFileCnf = $service->getVolume('config-file.cnf');
        $configFileCnf->setData($form->system_file['config-file.cnf'] ?? '');

        $this->serviceRepo->save($configFileCnf);

        $this->updateDatastore($service, $form);

        $this->updateSecrets($service, $form);

        $this->userFilesUpdate($service, $form);

        return $service;
    }

    protected function getOpenBindPort(Entity\Docker\Project $project) : int
    {
        $bindPortMetas = $this->serviceRepo->getProjectBindPorts($project);

        $ports = [];
        foreach ($bindPortMetas as $meta) {
            if (!$data = $meta->getData()) {
                continue;
            }

            $ports []= $data[0];
        }

        for ($i = 3307; $i < 65535; $i++) {
            if (!in_array($i, $ports)) {
                return $i;
            }
        }

        return 3306;
    }

    protected function createSecrets(
        Entity\Docker\Service $service,
        Form\Docker\Service\MySQLCreate $form
    ) {
        $sec  = '/run/secrets';
        $slug = $service->getSlug();

        // mysql_host

        $host = new Entity\Docker\Secret();
        $host->setName("{$slug}-mysql_host")
            ->setFile("./secrets/{$slug}-mysql_host")
            ->setContents($slug)
            ->setProject($service->getProject())
            ->setOwner($service);

        $hostSS = new Entity\Docker\ServiceSecret();
        $hostSS->setProjectSecret($host)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-mysql_host");

        $host->addServiceSecret($hostSS);

        // mysql_root_password

        $rootPw = new Entity\Docker\Secret();
        $rootPw->setName("{$slug}-mysql_root_password")
            ->setFile("./secrets/{$slug}-mysql_root_password")
            ->setContents($form->mysql_root_password)
            ->setProject($service->getProject())
            ->setOwner($service);

        $rootPwSS = new Entity\Docker\ServiceSecret();
        $rootPwSS->setProjectSecret($rootPw)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-mysql_root_password");

        $rootPw->addServiceSecret($rootPwSS);

        // mysql_database

        $database = new Entity\Docker\Secret();
        $database->setName("{$slug}-mysql_database")
            ->setFile("./secrets/{$slug}-mysql_database")
            ->setContents($form->mysql_database)
            ->setProject($service->getProject())
            ->setOwner($service);

        $databaseSS = new Entity\Docker\ServiceSecret();
        $databaseSS->setProjectSecret($database)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-mysql_database");

        $database->addServiceSecret($databaseSS);

        // mysql_user

        $user = new Entity\Docker\Secret();
        $user->setName("{$slug}-mysql_user")
            ->setFile("./secrets/{$slug}-mysql_user")
            ->setContents($form->mysql_user)
            ->setProject($service->getProject())
            ->setOwner($service);

        $userSS = new Entity\Docker\ServiceSecret();
        $userSS->setProjectSecret($user)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-mysql_user");

        $user->addServiceSecret($userSS);

        // mysql_password

        $password = new Entity\Docker\Secret();
        $password->setName("{$slug}-mysql_password")
            ->setFile("./secrets/{$slug}-mysql_password")
            ->setContents($form->mysql_password)
            ->setProject($service->getProject())
            ->setOwner($service);

        $passwordSS = new Entity\Docker\ServiceSecret();
        $passwordSS->setProjectSecret($password)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-mysql_password");

        $password->addServiceSecret($passwordSS);

        $service->addSecret($hostSS)
            ->addSecret($rootPwSS)
            ->addSecret($databaseSS)
            ->addSecret($userSS)
            ->addSecret($passwordSS);

        $this->serviceRepo->save(
            $host, $hostSS,
            $rootPw, $rootPwSS,
            $database, $databaseSS,
            $user, $userSS,
            $password, $passwordSS,
            $service
        );
    }

    protected function updateSecrets(
        Entity\Docker\Service $service,
        Form\Docker\Service\MySQLCreate $form
    ) {
        $secrets = $this->getSecrets($service);

        $mysql_root_password = $secrets['mysql_root_password']->getProjectSecret();
        $mysql_database      = $secrets['mysql_database']->getProjectSecret();
        $mysql_user          = $secrets['mysql_user']->getProjectSecret();
        $mysql_password      = $secrets['mysql_password']->getProjectSecret();

        $mysql_root_password->setContents($form->mysql_root_password);
        $mysql_database->setContents($form->mysql_database);
        $mysql_user->setContents($form->mysql_user);
        $mysql_password->setContents($form->mysql_password);

        $this->serviceRepo->save(
            $mysql_root_password,
            $mysql_database,
            $mysql_user,
            $mysql_password
        );
    }

    /**
     * @param Entity\Docker\Service $service
     * @return Entity\Docker\ServiceSecret[]
     */
    protected function getSecrets(Entity\Docker\Service $service) : array
    {
        $slug = $service->getSlug();

        return [
            'mysql_host'          => $service->getSecret("{$slug}-mysql_host"),
            'mysql_root_password' => $service->getSecret("{$slug}-mysql_root_password"),
            'mysql_database'      => $service->getSecret("{$slug}-mysql_database"),
            'mysql_user'          => $service->getSecret("{$slug}-mysql_user"),
            'mysql_password'      => $service->getSecret("{$slug}-mysql_password"),
        ];
    }
}
