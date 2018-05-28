<?php

namespace Dashtainer\Domain\Docker\ServiceWorker;

use Dashtainer\Domain;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;
use Dashtainer\Util;
use Dashtainer\Validator\Constraints;

abstract class WorkerAbstract implements WorkerInterface
{
    protected const INTERNAL_SECRETS = [];

    /** @var Domain\Docker\Network */
    protected $networkDomain;

    /** @var Domain\Docker\Secret */
    protected $secretDomain;

    /** @var Repository\Docker\Service */
    protected $serviceRepo;

    /** @var Repository\Docker\ServiceType */
    protected $serviceTypeRepo;

    public function __construct(
        Repository\Docker\Service $serviceRepo,
        Repository\Docker\ServiceType $serviceTypeRepo,
        Domain\Docker\Network $networkDomain,
        Domain\Docker\Secret $secretDomain
    ) {
        $this->serviceRepo     = $serviceRepo;
        $this->serviceTypeRepo = $serviceTypeRepo;

        $this->networkDomain = $networkDomain;
        $this->secretDomain  = $secretDomain;
    }

    public function getCreateParams(Entity\Docker\Project $project): array
    {
        return [
            'networkName' => $networkName = $this->networkDomain->generateName($project),
            'networks'    => $this->getCreateNetworks($project),
            'secrets'     => $this->getCreateSecrets($project),
        ];
    }

    public function getViewParams(Entity\Docker\Service $service) : array
    {
        return [
            'networks' => $this->getViewNetworks($service),
            'secrets'  => $this->getViewSecrets($service),
        ];
    }

    public function delete(Entity\Docker\Service $service)
    {
        $metas = [];
        foreach ($service->getMetas() as $meta) {
            $service->removeMeta($meta);

            $metas []= $meta;
        }

        $secrets = [];
        foreach ($service->getSecrets() as $serviceSecret) {
            $projectSecret = $serviceSecret->getProjectSecret();

            // Service owns Secret, remove Secret from other Services
            if ($projectSecret->getOwner() === $service) {
                foreach ($projectSecret->getServiceSecrets() as $childServiceSecret) {
                    $projectSecret->removeServiceSecret($childServiceSecret);
                    $childServiceSecret->setProjectSecret(null);

                    $secrets []= $childServiceSecret;
                }
            }

            $projectSecret->removeServiceSecret($serviceSecret);

            $serviceSecret->setProjectSecret(null);
            $serviceSecret->setService(null);

            $service->removeSecret($serviceSecret);

            $secrets []= $projectSecret;
            $secrets []= $serviceSecret;
        }

        $volumes = [];
        foreach ($service->getVolumes() as $volume) {
            $service->removeVolume($volume);

            $volumes []= $volume;
        }

        if ($parent = $service->getParent()) {
            $service->setParent(null);
            $parent->removeChild($service);

            $this->serviceRepo->save($parent);
        }

        $children = [];
        foreach ($service->getChildren() as $child) {
            $child->setParent(null);
            $service->removeChild($child);

            $children []= $child;
        }

        $this->serviceRepo->delete(...$metas, ...$secrets, ...$volumes, ...$children);
        $this->serviceRepo->delete($service);
    }

    /**
     * @param Entity\Docker\Service     $service
     * @param Constraints\UserFileTrait $form
     */
    protected function userFilesCreate(
        Entity\Docker\Service $service,
        $form
    ) {
        $files = [];
        foreach ($form->user_file as $fileConfig) {
            $name = Util\Strings::filename($fileConfig['filename']);

            $file = new Entity\Docker\ServiceVolume();
            $file->setName($name)
                ->setSource("\$PWD/{$service->getSlug()}/{$name}")
                ->setTarget($fileConfig['target'])
                ->setData($fileConfig['data'])
                ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                ->setOwner(Entity\Docker\ServiceVolume::OWNER_USER)
                ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                ->setService($service);

            $service->addVolume($file);

            $files []= $file;
        }

        if (!empty($files)) {
            $this->serviceRepo->save($service, ...$files);
        }
    }

    /**
     * @param Entity\Docker\Service     $service
     * @param Constraints\UserFileTrait $form
     */
    protected function userFilesUpdate(
        Entity\Docker\Service $service,
        $form
    ) {
        $existingUserFiles = $service->getVolumesByOwner(
            Entity\Docker\ServiceVolume::OWNER_USER
        );

        $files = [];
        foreach ($form->user_file as $id => $fileConfig) {
            $name = Util\Strings::filename($fileConfig['filename']);

            if (empty($existingUserFiles[$id])) {
                $file = new Entity\Docker\ServiceVolume();
                $file->setName($name)
                    ->setSource("\$PWD/{$service->getSlug()}/{$name}")
                    ->setTarget($fileConfig['target'])
                    ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
                    ->setData($fileConfig['data'])
                    ->setOwner(Entity\Docker\ServiceVolume::OWNER_USER)
                    ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE)
                    ->setService($service);

                $service->addVolume($file);

                $files []= $file;

                continue;
            }

            /** @var Entity\Docker\ServiceVolume $file */
            $file = $existingUserFiles[$id];
            unset($existingUserFiles[$id]);

            $file->setName($name)
                ->setSource("\$PWD/{$service->getSlug()}/{$name}")
                ->setTarget($fileConfig['target'])
                ->setData($fileConfig['data']);

            $files []= $file;
        }

        if (!empty($files)) {
            $this->serviceRepo->save($service, ...$files);
        }

        foreach ($existingUserFiles as $file) {
            $service->removeVolume($file);
            $this->serviceRepo->delete($file);
            $this->serviceRepo->save($service);
        }
    }

    /**
     * @param Entity\Docker\Service         $service
     * @param Constraints\ProjectFilesTrait $form
     */
    protected function projectFilesCreate(
        Entity\Docker\Service $service,
        $form
    ) {
        if ($form->project_files['type'] == 'local') {
            $projectFilesMeta = new Entity\Docker\ServiceMeta();
            $projectFilesMeta->setName('project_files')
                ->setData([
                    'type'   => 'local',
                    'source' => $form->project_files['local']['source'],
                ])
                ->setService($service);

            $service->addMeta($projectFilesMeta);

            $projectFilesSource = new Entity\Docker\ServiceVolume();
            $projectFilesSource->setName('project_files_source')
                ->setSource($form->project_files['local']['source'])
                ->setTarget('/var/www')
                ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_CACHED)
                ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_DIR)
                ->setService($service);

            $service->addVolume($projectFilesSource);

            $this->serviceRepo->save(
                $projectFilesMeta, $projectFilesSource, $service
            );
        }
    }

    /**
     * @param Entity\Docker\Service         $service
     * @param Constraints\ProjectFilesTrait $form
     */
    protected function projectFilesUpdate(
        Entity\Docker\Service $service,
        $form
    ) {
        $projectFilesMeta   = $service->getMeta('project_files');
        $projectFilesSource = $service->getVolume('project_files_source');

        if ($form->project_files['type'] == 'local') {
            $projectFilesMeta->setData([
                'type'   => 'local',
                'source' => $form->project_files['local']['source'],
            ]);

            if (!$projectFilesSource) {
                $projectFilesSource = new Entity\Docker\ServiceVolume();
                $projectFilesSource->setName('project_files_source')
                    ->setTarget('/var/www')
                    ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_CACHED)
                    ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
                    ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_DIR)
                    ->setService($service);
            }

            $projectFilesSource->setSource($form->project_files['local']['source']);

            $this->serviceRepo->save(
                $projectFilesMeta, $projectFilesSource, $service
            );
        }

        if ($form->project_files['type'] !== 'local' && $projectFilesSource) {
            $projectFilesSource->setService(null);
            $service->removeVolume($projectFilesSource);

            $this->serviceRepo->delete($projectFilesSource);

            $this->serviceRepo->save($service);
        }

        // todo: Add support for non-local project files source, ie github
    }

    /**
     * @param Entity\Docker\Service      $service
     * @param Constraints\DatastoreTrait $form
     * @param string                     $target
     */
    protected function createDatastore(
        Entity\Docker\Service $service,
        $form,
        string $target
    ) {
        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData([$form->datastore])
            ->setService($service);

        $service->addMeta($dataStoreMeta);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setTarget($target)
            ->setConsistency(Entity\Docker\ServiceVolume::CONSISTENCY_DELEGATED)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_DIR)
            ->setService($service);

        if ($form->datastore == 'local') {
            $serviceDatastoreVol->setSource("\$PWD/{$service->getSlug()}/datadir")
                ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

            $service->addVolume($serviceDatastoreVol);

            $this->serviceRepo->save($dataStoreMeta, $serviceDatastoreVol, $service);
        }

        if ($form->datastore !== 'local') {
            $projectDatastoreVol = new Entity\Docker\Volume();
            $projectDatastoreVol->setName("{$service->getSlug()}-datastore")
                ->setProject($service->getProject())
                ->addServiceVolume($serviceDatastoreVol);

            $serviceDatastoreVol->setSource($projectDatastoreVol->getSlug())
                ->setType(Entity\Docker\ServiceVolume::TYPE_VOLUME)
                ->setProjectVolume($projectDatastoreVol);

            $service->addVolume($serviceDatastoreVol);

            $this->serviceRepo->save(
                $projectDatastoreVol, $dataStoreMeta, $serviceDatastoreVol, $service
            );
        }
    }

    /**
     * @param Entity\Docker\Service      $service
     * @param Constraints\DatastoreTrait $form
     */
    protected function updateDatastore(
        Entity\Docker\Service $service,
        $form
    ) {
        $dataStoreMeta = $service->getMeta('datastore');
        $dataStoreMeta->setData([$form->datastore]);

        $this->serviceRepo->save($dataStoreMeta);

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
    }

    protected function projectFilesViewParams(Entity\Docker\Service $service) : array
    {
        $projectFilesMeta = $service->getMeta('project_files');

        $projectFilesLocal = [
            'type'   => 'local',
            'source' => '',
        ];
        if ($projectFilesMeta->getData()['type'] == 'local') {
            $projectFilesLocal['source'] = $projectFilesMeta->getData()['source'];
        }

        return [
            'type'  => $projectFilesMeta->getData()['type'],
            'local' => $projectFilesLocal,
        ];
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function addToPrivateNetworks(Entity\Docker\Service $service, $form)
    {
        $this->networkDomain->joinNetworks($service, $form->networks_join);
        $this->networkDomain->createNetworksForService($service, $form->networks_create);
        $this->networkDomain->deleteEmptyNetworks($service->getProject());
    }

    protected function getCreateNetworks(Entity\Docker\Project $project) : array
    {
        return [
            'joined'   => [],
            'unjoined' => $this->networkDomain->getPrivateNetworks($project),
        ];
    }

    protected function getViewNetworks(Entity\Docker\Service $service) : array
    {
        return [
            'joined'   => $this->networkDomain->findByService($service),
            'unjoined' => $this->networkDomain->findByNotService($service),
        ];
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function createSecrets(
        Entity\Docker\Service $service,
        $form
    ) {
        $internalSecrets = $this->internalSecretsArray($service, $form);

        $ownedSecrets = [];
        foreach ($form->owned_secrets as $ownedSecret) {
            // Don't create Secrets that have id (already exist)
            if (!empty($ownedSecret['id'])) {
                continue;
            }

            $ownedSecrets [$ownedSecret['name']]= $ownedSecret['contents'];
        }

        // Create internal secrets
        $this->secretDomain->createOwnedSecrets($service, $internalSecrets, true);

        // Don't allow ownedSecrets to override internalSecrets
        $ownedSecrets = array_diff_key($ownedSecrets, $internalSecrets);

        // Create owned secrets
        $this->secretDomain->createOwnedSecrets($service, $ownedSecrets);

        // Create granted secrets
        $this->secretDomain->grantSecrets($service, $form->grant_secrets);
    }

    /**
     * @param Entity\Docker\Service              $service
     * @param Form\Docker\Service\CreateAbstract $form
     */
    protected function updateSecrets(
        Entity\Docker\Service $service,
        $form
    ) {
        // Update internal secrets
        $this->secretDomain->updateInternal(
            $service,
            $this->internalSecretsArray($service, $form)
        );

        // Update owned secrets
        $this->secretDomain->updateOwned($service, $form->owned_secrets);

        // Update granted secrets
        $this->secretDomain->grantSecrets($service, $form->grant_secrets);
    }

    protected function getCreateSecrets(Entity\Docker\Project $project) : array
    {
        $allSecrets = $this->secretDomain->getAll($project);

        return [
            'all'       => $allSecrets,
            'internal'  => [],
            'owned'     => [],
            'granted'   => [],
            'grantable' => $allSecrets,
        ];
    }

    protected function getViewSecrets(Entity\Docker\Service $service) : array
    {
        return [
            'all'       => $this->secretDomain->getAll($service->getProject()),
            'internal'  => $this->secretDomain->getInternal($service),
            'owned'     => $this->secretDomain->getNotInternal($service),
            'granted'   => $this->secretDomain->getGranted($service),
            'grantable' => $this->secretDomain->getNotGranted($service),
        ];
    }

    /**
     * Returns array of [secret name => contents]
     *
     * @param Entity\Docker\Service $service
     * @param $form
     * @return array
     */
    abstract protected function internalSecretsArray(
        Entity\Docker\Service $service,
        $form
    ) : array;
}
