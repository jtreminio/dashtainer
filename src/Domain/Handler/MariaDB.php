<?php

namespace Dashtainer\Domain\Handler;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class MariaDB extends HandlerAbstract implements CrudInterface
{
    /** @var Repository\DockerNetworkRepository */
    protected $networkRepo;

    public function __construct(
        Repository\DockerProjectRepository $projectRepo,
        Repository\DockerServiceRepository $serviceRepo,
        Repository\DockerNetworkRepository $networkRepo
    ) {
        $this->networkRepo = $networkRepo;
        $this->serviceRepo = $serviceRepo;
    }

    public function getServiceTypeSlug() : string
    {
        return 'mariadb';
    }

    public function getCreateForm(
        Entity\DockerServiceType $serviceType = null
    ) : Form\Service\CreateAbstract {
        return new Form\Service\MariaDBCreate();
    }

    /**
     * @param Form\Service\MariaDBCreate $form
     * @return Entity\DockerService
     */
    public function create($form) : Entity\DockerService
    {
        $service = new Entity\DockerService();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) number_format($form->version, 1);

        $service->setImage("mariadb:{$version}")
            ->setRestart(Entity\DockerService::RESTART_ALWAYS);

        $service->setEnvironments([
            'MYSQL_ROOT_PASSWORD' => $form->mysql_root_password,
            'MYSQL_DATABASE'      => $form->mysql_database,
            'MYSQL_USER'          => $form->mysql_user,
            'MYSQL_PASSWORD'      => $form->mysql_password,
        ]);

        $privateNetwork = $this->networkRepo->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->serviceRepo->save($service, $privateNetwork);

        $dataStoreMeta = new Entity\DockerServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData([$form->datastore])
            ->setService($service);

        $service->addMeta($dataStoreMeta);

        $versionMeta = new Entity\DockerServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->serviceRepo->save($dataStoreMeta, $versionMeta, $service);

        $myCnf = new Entity\DockerServiceVolume();
        $myCnf->setName('my.cnf')
            ->setSource("\$PWD/{$service->getSlug()}/my.cnf")
            ->setTarget('/etc/mysql/my.cnf')
            ->setData($form->file['my.cnf'] ?? '')
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $configFileCnf = new Entity\DockerServiceVolume();
        $configFileCnf->setName('config-file.cnf')
            ->setSource("\$PWD/{$service->getSlug()}/config-file.cnf")
            ->setTarget('/etc/mysql/conf.d/config-file.cnf')
            ->setData($form->file['config-file.cnf'] ?? '')
            ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
            ->setType(Entity\DockerServiceVolume::TYPE_FILE)
            ->setService($service);

        $service->addVolume($myCnf)
            ->addVolume($configFileCnf);

        $this->serviceRepo->save($myCnf, $configFileCnf, $service);

        $this->customFilesCreate($service, $form);

        return $service;
    }

    public function getCreateParams(Entity\DockerProject $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\DockerService $service) : array
    {
        $version   = $service->getMeta('version')->getData()[0];
        $version   = (string) number_format($version, 1);
        $datastore = $service->getMeta('datastore')->getData()[0];

        $env = $service->getEnvironments();

        $mysql_root_password = $env['MYSQL_ROOT_PASSWORD'];
        $mysql_database      = $env['MYSQL_DATABASE'];
        $mysql_user          = $env['MYSQL_USER'];
        $mysql_password      = $env['MYSQL_PASSWORD'];

        $myCnf         = $service->getVolume('my.cnf');
        $configFileCnf = $service->getVolume('config-file.cnf');

        $customFiles = $service->getVolumesByOwner(Entity\DockerServiceVolume::OWNER_USER);

        return [
            'version'             => $version,
            'datastore'           => $datastore,
            'mysql_root_password' => $mysql_root_password,
            'mysql_database'      => $mysql_database,
            'mysql_user'          => $mysql_user,
            'mysql_password'      => $mysql_password,
            'configFiles'         => [
                'my.cnf'          => $myCnf,
                'config-file.cnf' => $configFileCnf,
            ],
            'customFiles'         => $customFiles,
        ];
    }

    /**
     * @param Entity\DockerService      $service
     * @param Form\Service\MariaDBCreate $form
     * @return Entity\DockerService
     */
    public function update(
        Entity\DockerService $service,
        $form
    ) : Entity\DockerService {
        $service->setEnvironments([
            'MYSQL_ROOT_PASSWORD' => $form->mysql_root_password,
            'MYSQL_DATABASE'      => $form->mysql_database,
            'MYSQL_USER'          => $form->mysql_user,
            'MYSQL_PASSWORD'      => $form->mysql_password,
        ]);

        $dataStoreMeta = $service->getMeta('datastore');
        $dataStoreMeta->setData([$form->datastore]);

        $this->serviceRepo->save($dataStoreMeta);

        $myCnf = $service->getVolume('my.cnf');
        $myCnf->setData($form->file['my.cnf'] ?? '');

        $configFileCnf = $service->getVolume('config-file.cnf');
        $configFileCnf->setData($form->file['config-file.cnf'] ?? '');

        $this->serviceRepo->save($myCnf, $configFileCnf);

        $this->customFilesUpdate($service, $form);

        return $service;
    }
}
