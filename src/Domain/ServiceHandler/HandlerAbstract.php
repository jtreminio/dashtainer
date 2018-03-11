<?php

namespace Dashtainer\Domain\ServiceHandler;

use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

abstract class HandlerAbstract implements CrudInterface
{
    /** @var Repository\DockerServiceRepository */
    protected $serviceRepo;

    public function delete(Entity\DockerService $service)
    {
        $metas = [];
        foreach ($service->getMetas() as $meta) {
            $service->removeMeta($meta);

            $metas []= $meta;
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

        $this->serviceRepo->delete(...$metas, ...$volumes, ...$children);
        $this->serviceRepo->delete($service);
    }

    /**
     * @param Entity\DockerService         $service
     * @param Form\Service\CustomFileTrait $form
     */
    protected function customFilesCreate(
        Entity\DockerService $service,
        $form
    ) {
        $files = [];
        foreach ($form->custom_file as $fileConfig) {
            $file = new Entity\DockerServiceVolume();
            $file->setName($fileConfig['filename'])
                ->setSource("\$PWD/{$service->getSlug()}/{$fileConfig['filename']}")
                ->setTarget($fileConfig['target'])
                ->setData($fileConfig['data'])
                ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
                ->setOwner(Entity\DockerServiceVolume::OWNER_USER)
                ->setFiletype(Entity\DockerServiceVolume::FILETYPE_FILE)
                ->setService($service);

            $service->addVolume($file);

            $files []= $file;
        }

        if (!empty($files)) {
            $this->serviceRepo->save($service, ...$files);
        }
    }

    /**
     * @param Entity\DockerService         $service
     * @param Form\Service\CustomFileTrait $form
     */
    protected function customFilesUpdate(
        Entity\DockerService $service,
        $form
    ) {
        $existingUserFiles = $service->getVolumesByOwner(
            Entity\DockerServiceVolume::OWNER_USER
        );

        $files = [];
        foreach ($form->custom_file as $id => $fileConfig) {
            if (empty($existingUserFiles[$id])) {
                $file = new Entity\DockerServiceVolume();
                $file->setName($fileConfig['filename'])
                    ->setSource("\$PWD/{$service->getSlug()}/{$fileConfig['filename']}")
                    ->setTarget($fileConfig['target'])
                    ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_DELEGATED)
                    ->setData($fileConfig['data'])
                    ->setOwner(Entity\DockerServiceVolume::OWNER_USER)
                    ->setFiletype(Entity\DockerServiceVolume::FILETYPE_FILE)
                    ->setService($service);

                $service->addVolume($file);

                $files []= $file;

                continue;
            }

            /** @var Entity\DockerServiceVolume $file */
            $file = $existingUserFiles[$id];
            unset($existingUserFiles[$id]);

            $file->setName($fileConfig['filename'])
                ->setSource("\$PWD/{$service->getSlug()}/{$fileConfig['filename']}")
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
     * @param Entity\DockerService           $service
     * @param Form\Service\ProjectFilesTrait $form
     */
    protected function projectFilesCreate(
        Entity\DockerService $service,
        $form
    ) {
        if ($form->project_files['type'] == 'local') {
            $projectFilesMeta = new Entity\DockerServiceMeta();
            $projectFilesMeta->setName('project_files')
                ->setData([
                    'type'   => 'local',
                    'source' => $form->project_files['local']['source'],
                ])
                ->setService($service);

            $service->addMeta($projectFilesMeta);

            $projectFilesSource = new Entity\DockerServiceVolume();
            $projectFilesSource->setName('project_files_source')
                ->setSource($form->project_files['local']['source'])
                ->setTarget('/var/www')
                ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_CACHED)
                ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
                ->setFiletype(Entity\DockerServiceVolume::FILETYPE_DIR)
                ->setService($service);

            $this->serviceRepo->save($projectFilesMeta, $projectFilesSource, $service);
        }
    }

    /**
     * @param Entity\DockerService           $service
     * @param Form\Service\ProjectFilesTrait $form
     */
    protected function projectFilesUpdate(
        Entity\DockerService $service,
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
                $projectFilesSource = new Entity\DockerServiceVolume();
                $projectFilesSource->setName('project_files_source')
                    ->setTarget('/var/www')
                    ->setConsistency(Entity\DockerServiceVolume::CONSISTENCY_CACHED)
                    ->setOwner(Entity\DockerServiceVolume::OWNER_SYSTEM)
                    ->setFiletype(Entity\DockerServiceVolume::FILETYPE_DIR)
                    ->setService($service);
            }

            $projectFilesSource->setSource($form->project_files['local']['source']);

            $this->serviceRepo->save($projectFilesMeta, $projectFilesSource, $service);
        }

        if ($form->project_files['type'] !== 'local' && $projectFilesSource) {
            $projectFilesSource->setService(null);
            $service->removeVolume($projectFilesSource);

            $this->serviceRepo->delete($projectFilesSource);

            $this->serviceRepo->save($service);
        }

        // todo: Add support for non-local project files source, ie github
    }

    protected function projectFilesViewParams(Entity\DockerService $service) : array
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
}
