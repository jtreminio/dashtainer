<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class MariaDB extends WorkerAbstract implements WorkerInterface
{
    /** @var Repository\Docker\Network */
    protected $repoDockNetwork;

    public function __construct(
        Repository\Docker\Service $repoDockService,
        Repository\Docker\Network $repoDockNetwork
    ) {
        $this->repoDockNetwork = $repoDockNetwork;
        $this->repoDockService = $repoDockService;
    }

    public function getServiceTypeSlug() : string
    {
        return 'mariadb';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
        return new Form\Docker\Service\MariaDBCreate();
    }

    /**
     * @param Form\Docker\Service\MariaDBCreate $form
     * @return Entity\Docker\Service
     */
    public function create($form) : Entity\Docker\Service
    {
        $service = new Entity\Docker\Service();
        $service->setName($form->name)
            ->setType($form->type)
            ->setProject($form->project);

        $version = (string) number_format($form->version, 1);

        $service->setImage("mariadb:{$version}")
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS);

        $service->setEnvironments([
            'MYSQL_ROOT_PASSWORD' => $form->mysql_root_password,
            'MYSQL_DATABASE'      => $form->mysql_database,
            'MYSQL_USER'          => $form->mysql_user,
            'MYSQL_PASSWORD'      => $form->mysql_password,
        ]);

        $privateNetwork = $this->repoDockNetwork->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->repoDockService->save($service, $privateNetwork);

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData([$form->datastore])
            ->setService($service);

        $service->addMeta($dataStoreMeta);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$form->version])
            ->setService($service);

        $service->addMeta($versionMeta);

        $this->repoDockService->save($dataStoreMeta, $versionMeta, $service);

        $myCnf = new Entity\Docker\ServiceVolume();
        $myCnf->setName('my.cnf')
            ->setSource("\$PWD/{$service->getSlug()}/my.cnf")
            ->setTarget('/etc/mysql/my.cnf')
            ->setData($form->file['my.cnf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $configFileCnf = new Entity\Docker\ServiceVolume();
        $configFileCnf->setName('config-file.cnf')
            ->setSource("\$PWD/{$service->getSlug()}/config-file.cnf")
            ->setTarget('/etc/mysql/conf.d/config-file.cnf')
            ->setData($form->file['config-file.cnf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($myCnf)
            ->addVolume($configFileCnf);

        $this->repoDockService->save($myCnf, $configFileCnf, $service);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setSource("\$PWD/{$service->getSlug()}/datadir")
            ->setTarget('/var/lib/mysql')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_DIR)
            ->setService($service);

        if ($form->datastore == 'local') {
            $serviceDatastoreVol->setSource("\$PWD/{$service->getSlug()}/datadir")
                ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

            $service->addVolume($serviceDatastoreVol);

            $this->repoDockService->save($serviceDatastoreVol, $service);
        }

        if ($form->datastore !== 'local') {
            $projectDatastoreVol = new Entity\Docker\Volume();
            $projectDatastoreVol->setName("{$service->getSlug()}-datastore")
                ->setProject($service->getProject());

            $serviceDatastoreVol->setSource($projectDatastoreVol->getSlug())
                ->setType(Entity\Docker\ServiceVolume::TYPE_VOLUME);

            $projectDatastoreVol->addServiceVolume($serviceDatastoreVol);
            $serviceDatastoreVol->setProjectVolume($projectDatastoreVol);
            $service->addVolume($serviceDatastoreVol);

            $this->repoDockService->save(
                $projectDatastoreVol, $serviceDatastoreVol, $service
            );
        }

        $this->customFilesCreate($service, $form);

        return $service;
    }

    public function getCreateParams(Entity\Docker\Project $project) : array
    {
        return [];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
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

        $customFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

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
     * @param Entity\Docker\Service      $service
     * @param Form\Docker\Service\MariaDBCreate $form
     * @return Entity\Docker\Service
     */
    public function update(
        Entity\Docker\Service $service,
        $form
    ) : Entity\Docker\Service {
        $service->setEnvironments([
            'MYSQL_ROOT_PASSWORD' => $form->mysql_root_password,
            'MYSQL_DATABASE'      => $form->mysql_database,
            'MYSQL_USER'          => $form->mysql_user,
            'MYSQL_PASSWORD'      => $form->mysql_password,
        ]);

        $dataStoreMeta = $service->getMeta('datastore');
        $dataStoreMeta->setData([$form->datastore]);

        $this->repoDockService->save($dataStoreMeta);

        $myCnf = $service->getVolume('my.cnf');
        $myCnf->setData($form->file['my.cnf'] ?? '');

        $configFileCnf = $service->getVolume('config-file.cnf');
        $configFileCnf->setData($form->file['config-file.cnf'] ?? '');

        $this->repoDockService->save($myCnf, $configFileCnf);

        $serviceDatastoreVol = $service->getVolume('datastore');
        $projectDatastoreVol = $serviceDatastoreVol->getProjectVolume();

        if ($form->datastore == 'local' && $projectDatastoreVol) {
            $projectDatastoreVol->removeServiceVolume($serviceDatastoreVol);
            $serviceDatastoreVol->setProjectVolume(null);

            $serviceDatastoreVol->setName('datastore')
                ->setSource("\$PWD/{$service->getSlug()}/datadir")
                ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

            $this->repoDockService->save($serviceDatastoreVol);

            if ($projectDatastoreVol->getServiceVolumes()->isEmpty()) {
                $this->repoDockService->delete($projectDatastoreVol);
            }
        }

        if ($form->datastore !== 'local') {
            if (!$projectDatastoreVol) {
                $projectDatastoreVol = new Entity\Docker\Volume();
                $projectDatastoreVol->setName("{$service->getSlug()}-datastore")
                    ->setProject($service->getProject());

                $projectDatastoreVol->addServiceVolume($serviceDatastoreVol);
                $serviceDatastoreVol->setProjectVolume($projectDatastoreVol);
            }

            $serviceDatastoreVol->setSource($projectDatastoreVol->getSlug())
                ->setType(Entity\Docker\ServiceVolume::TYPE_VOLUME);

            $this->repoDockService->save($projectDatastoreVol, $serviceDatastoreVol);
        }

        $this->customFilesUpdate($service, $form);

        return $service;
    }
}
