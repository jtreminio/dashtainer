<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

class PostgreSQL extends WorkerAbstract implements WorkerInterface
{
    /** @var Repository\Docker\Network */
    protected $networkRepo;

    public function __construct(
        Repository\Docker\Service $serviceRepo,
        Repository\Docker\Network $networkRepo
    ) {
        $this->serviceRepo = $serviceRepo;
        $this->networkRepo = $networkRepo;
    }

    public function getServiceTypeSlug() : string
    {
        return 'postgresql';
    }

    public function getCreateForm(
        Entity\Docker\ServiceType $serviceType = null
    ) : Form\Docker\Service\CreateAbstract {
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

        $service->setEnvironments([
            'POSTGRES_DB'       => $form->postgres_db,
            'POSTGRES_USER'     => $form->postgres_user,
            'POSTGRES_PASSWORD' => $form->postgres_password,
        ]);

        $privateNetwork = $this->networkRepo->getPrimaryPrivateNetwork(
            $service->getProject()
        );

        $service->addNetwork($privateNetwork);

        $this->serviceRepo->save($service, $privateNetwork);

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

        $this->serviceRepo->save($dataStoreMeta, $versionMeta, $service);

        $configFileConf = new Entity\Docker\ServiceVolume();
        $configFileConf->setName('postgresql.conf')
            ->setSource("\$PWD/{$service->getSlug()}/postgresql.conf")
            ->setTarget('/etc/postgresql/postgresql.conf')
            ->setData($form->file['postgresql.conf'] ?? '')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
            ->setService($service);

        $service->addVolume($configFileConf);

        $this->serviceRepo->save($configFileConf, $service);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setSource("\$PWD/{$service->getSlug()}/datadir")
            ->setTarget('/var/lib/postgresql/data')
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_DIR)
            ->setService($service);

        if ($form->datastore == 'local') {
            $serviceDatastoreVol->setSource("\$PWD/{$service->getSlug()}/datadir")
                ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

            $service->addVolume($serviceDatastoreVol);

            $this->serviceRepo->save($serviceDatastoreVol, $service);
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

            $this->serviceRepo->save(
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

        $postgres_db       = $env['POSTGRES_DB'];
        $postgres_user     = $env['POSTGRES_USER'];
        $postgres_password = $env['POSTGRES_PASSWORD'];

        $configFileConf = $service->getVolume('postgresql.conf');

        $customFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        return [
            'version'           => $version,
            'datastore'         => $datastore,
            'postgres_db'       => $postgres_db,
            'postgres_user'     => $postgres_user,
            'postgres_password' => $postgres_password,
            'configFiles'       => [
                'postgresql.conf' => $configFileConf,
            ],
            'customFiles'       => $customFiles,
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
        $service->setEnvironments([
            'POSTGRES_DB'       => $form->postgres_db,
            'POSTGRES_USER'     => $form->postgres_user,
            'POSTGRES_PASSWORD' => $form->postgres_password,
        ]);

        $dataStoreMeta = $service->getMeta('datastore');
        $dataStoreMeta->setData([$form->datastore]);

        $this->serviceRepo->save($dataStoreMeta);

        $configFileConf = $service->getVolume('postgresql.conf');
        $configFileConf->setData($form->file['postgresql.conf'] ?? '');

        $this->serviceRepo->save($configFileConf);

        $serviceDatastoreVol = $service->getVolume('datastore');
        $projectDatastoreVol = $serviceDatastoreVol->getProjectVolume();

        if ($form->datastore == 'local' && $projectDatastoreVol) {
            $projectDatastoreVol->removeServiceVolume($serviceDatastoreVol);
            $serviceDatastoreVol->setProjectVolume(null);

            $serviceDatastoreVol->setName('datastore')
                ->setSource("\$PWD/{$service->getSlug()}/datadir")
                ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

            $this->serviceRepo->save($serviceDatastoreVol);

            if ($projectDatastoreVol->getServiceVolumes()->isEmpty()) {
                $this->serviceRepo->delete($projectDatastoreVol);
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

            $this->serviceRepo->save($projectDatastoreVol, $serviceDatastoreVol);
        }

        $this->customFilesUpdate($service, $form);

        return $service;
    }
}
