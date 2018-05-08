<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;

class PostgreSQL extends WorkerAbstract implements WorkerInterface
{
    public function getServiceTypeSlug() : string
    {
        return 'postgresql';
    }

    public function getCreateForm() : Form\Docker\Service\CreateAbstract
    {
        return new Form\Docker\Service\PostgreSQLCreate();
    }

    /**
     * @param Form\Docker\Service\PostgreSQLCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) number_format($form->version, 1);

        $service->setImage("postgres:{$version}")
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS);

        $sec  = '/run/secrets';
        $slug = $service->getSlug();

        $service->setEnvironments([
            'POSTGRES_DB_FILE'       => "{$sec}/{$slug}-postgres_db",
            'POSTGRES_USER_FILE'     => "{$sec}/{$slug}-postgres_user",
            'POSTGRES_PASSWORD_FILE' => "{$sec}/{$slug}-postgres_password",
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
        $servicePort  = $form->port_confirm ? ["{$form->port}:5432"] : [];

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('bind-port')
            ->setData($portMetaData)
            ->setService($service);

        $service->addMeta($portMeta)
            ->setPorts($servicePort);

        $this->serviceRepo->save($versionMeta, $portMeta, $service);

        $configFileConf = new Entity\Docker\ServiceVolume();
        $configFileConf->setName('postgresql.conf')
            ->setSource("\$PWD/{$service->getSlug()}/postgresql.conf")
            ->setTarget('/etc/postgresql/postgresql.conf')
            ->setData($form->system_file['postgresql.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($configFileConf);

        $this->serviceRepo->save($configFileConf, $service);

        $this->createDatastore($service, $form, '/var/lib/postgresql/data');

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

        $postgres_db       = $secrets['postgres_db']->getProjectSecret()->getContents();
        $postgres_user     = $secrets['postgres_user']->getProjectSecret()->getContents();
        $postgres_password = $secrets['postgres_password']->getProjectSecret()->getContents();

        $configFileConf = $service->getVolume('postgresql.conf');

        $userFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        return [
            'version'           => $version,
            'datastore'         => $datastore,
            'bindPort'          => $bindPort,
            'portConfirm'       => $portConfirm,
            'postgres_db'       => $postgres_db,
            'postgres_user'     => $postgres_user,
            'postgres_password' => $postgres_password,
            'systemFiles'       => [
                'postgresql.conf' => $configFileConf,
            ],
            'userFiles'         => $userFiles,
        ];
    }

    /**
     * @param Entity\Docker\Service                $service
     * @param Form\Docker\Service\PostgreSQLCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $this->addToPrivateNetworks($service, $form);

        $portMetaData = $form->port_confirm ? [$form->port] : [];
        $servicePort  = $form->port_confirm ? ["{$form->port}:5432"] : [];

        $portMeta = $service->getMeta('bind-port');
        $portMeta->setData($portMetaData);

        $this->serviceRepo->save($portMeta);

        $service->setPorts($servicePort);

        $configFileConf = $service->getVolume('postgresql.conf');
        $configFileConf->setData($form->system_file['postgresql.conf'] ?? '');

        $this->serviceRepo->save($configFileConf);

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

        for ($i = 5433; $i < 65535; $i++) {
            if (!in_array($i, $ports)) {
                return $i;
            }
        }

        return 5432;
    }

    protected function createSecrets(
        Entity\Docker\Service $service,
        Form\Docker\Service\PostgreSQLCreate $form
    ) {
        $sec  = '/run/secrets';
        $slug = $service->getSlug();

        // postgres_host

        $host = new Entity\Docker\Secret();
        $host->setName("{$slug}-postgres_host")
            ->setFile("./secrets/{$slug}-postgres_host")
            ->setContents($slug)
            ->setProject($service->getProject())
            ->setOwner($service);

        $hostSS = new Entity\Docker\ServiceSecret();
        $hostSS->setProjectSecret($host)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-postgres_host");

        $host->addServiceSecret($hostSS);

        // postgres_db

        $database = new Entity\Docker\Secret();
        $database->setName("{$slug}-postgres_db")
            ->setFile("./secrets/{$slug}-postgres_db")
            ->setContents($form->postgres_db)
            ->setProject($service->getProject())
            ->setOwner($service);

        $databaseSS = new Entity\Docker\ServiceSecret();
        $databaseSS->setProjectSecret($database)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-postgres_db");

        $database->addServiceSecret($databaseSS);

        // postgres_user

        $user = new Entity\Docker\Secret();
        $user->setName("{$slug}-postgres_user")
            ->setFile("./secrets/{$slug}-postgres_user")
            ->setContents($form->postgres_user)
            ->setProject($service->getProject())
            ->setOwner($service);

        $userSS = new Entity\Docker\ServiceSecret();
        $userSS->setProjectSecret($user)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-postgres_user");

        $user->addServiceSecret($userSS);

        // postgres_password

        $password = new Entity\Docker\Secret();
        $password->setName("{$slug}-postgres_password")
            ->setFile("./secrets/{$slug}-postgres_password")
            ->setContents($form->postgres_password)
            ->setProject($service->getProject())
            ->setOwner($service);

        $passwordSS = new Entity\Docker\ServiceSecret();
        $passwordSS->setProjectSecret($password)
            ->setService($service)
            ->setTarget("{$sec}/{$slug}-postgres_password");

        $password->addServiceSecret($passwordSS);

        $service->addSecret($hostSS)
            ->addSecret($databaseSS)
            ->addSecret($userSS)
            ->addSecret($passwordSS);

        $this->serviceRepo->save(
            $host, $hostSS,
            $database, $databaseSS,
            $user, $userSS,
            $password, $passwordSS,
            $service
        );
    }

    protected function updateSecrets(
        Entity\Docker\Service $service,
        Form\Docker\Service\PostgreSQLCreate $form
    ) {
        $secrets = $this->getSecrets($service);

        $postgres_db       = $secrets['postgres_db']->getProjectSecret();
        $postgres_user     = $secrets['postgres_user']->getProjectSecret();
        $postgres_password = $secrets['postgres_password']->getProjectSecret();

        $postgres_db->setContents($form->postgres_db);
        $postgres_user->setContents($form->postgres_user);
        $postgres_password->setContents($form->postgres_password);

        $this->serviceRepo->save(
            $postgres_db,
            $postgres_user,
            $postgres_password
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
            'postgres_host'     => $service->getSecret("{$slug}-postgres_host"),
            'postgres_db'       => $service->getSecret("{$slug}-postgres_db"),
            'postgres_user'     => $service->getSecret("{$slug}-postgres_user"),
            'postgres_password' => $service->getSecret("{$slug}-postgres_password"),
        ];
    }
}
