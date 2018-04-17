<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Beanstalkd;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BeanstalkdTest extends KernelTestCase
{
    /** @var Form\Docker\Service\BeanstalkdCreate */
    protected $form;

    /** @var MockObject|Repository\Docker\Network */
    protected $networkRepo;

    /** @var Entity\Docker\Project */
    protected $project;

    /** @var Entity\Docker\Network */
    protected $publicNetwork;

    protected $seededPrivateNetworks = [];

    /** @var MockObject|Repository\Docker\Service */
    protected $serviceRepo;

    /** @var Entity\Docker\ServiceType */
    protected $serviceType;

    /** @var MockObject|Repository\Docker\ServiceType */
    protected $serviceTypeRepo;

    /** @var Beanstalkd */
    protected $worker;

    protected function setUp()
    {
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->networkRepo = $this->getMockBuilder(Repository\Docker\Network::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $this->serviceRepo = $this->getMockBuilder(Repository\Docker\Service::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $this->serviceTypeRepo = $this->getMockBuilder(Repository\Docker\ServiceType::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $this->project = new Entity\Docker\Project();
        $this->project->setName('project-name');

        $this->publicNetwork = new Entity\Docker\Network();

        $this->project->addNetwork($this->publicNetwork);

        $this->serviceType = new Entity\Docker\ServiceType();
        $this->serviceType->setName('service-type-name');

        $this->form = new Form\Docker\Service\BeanstalkdCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';
        $this->form->file    = [
            'Dockerfile' => 'Dockerfile contents',
        ];
        $this->form->datastore = 'local';

        $this->worker = new Beanstalkd($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);

        $this->seedProjectWithPrivateNetworks();

        $this->networkRepo->expects($this->any())
            ->method('getPublicNetwork')
            ->will($this->returnValue($this->publicNetwork));
    }

    protected function seedProjectWithPrivateNetworks()
    {
        $privateNetworkA = new Entity\Docker\Network();
        $privateNetworkA->setName('private-network-a');

        $privateNetworkB = new Entity\Docker\Network();
        $privateNetworkB->setName('private-network-b');

        $privateNetworkC = new Entity\Docker\Network();
        $privateNetworkC->setName('private-network-c');

        $this->project->addNetwork($privateNetworkA)
            ->addNetwork($privateNetworkB)
            ->addNetwork($privateNetworkC);

        $this->seededPrivateNetworks = [
            'private-network-a' => $privateNetworkA,
            'private-network-b' => $privateNetworkB,
            'private-network-c' => $privateNetworkC,
        ];
    }

    public function testCreateReturnsServiceEntity()
    {
        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue([]));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->worker->create($this->form);

        $build = $service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
        $this->assertEquals('Dockerfile', $build->getDockerfile());

        $datastoreVolume = $service->getVolume('datastore');

        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($service->getVolume('Dockerfile'));
        $this->assertNotNull($datastoreVolume);
        $this->assertNull($datastoreVolume->getProjectVolume());

        $this->assertEquals('$PWD/service-name/datadir', $datastoreVolume->getSource());
    }

    public function testCreateReturnsServiceEntityWithNonLocalDatastore()
    {
        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue([]));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $this->form->datastore = 'volume';

        $service = $this->worker->create($this->form);

        $build = $service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
        $this->assertEquals('Dockerfile', $build->getDockerfile());

        $datastore = $service->getVolume('datastore');
        $projectDatastoreVolume = $datastore->getProjectVolume();

        $this->assertNotNull($service->getVolume('Dockerfile'));
        $this->assertNotNull($datastore);
        $this->assertNotNull($projectDatastoreVolume);

        $this->assertEquals('service-name-datastore', $projectDatastoreVolume->getName());
        $this->assertEquals($projectDatastoreVolume->getName(), $datastore->getSource());
    }

    public function testGetCreateParams()
    {
        $this->assertEquals([], $this->worker->getCreateParams($this->project));
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals('local', $params['datastore']);
        $this->assertSame(
            $service->getVolume('Dockerfile'),
            $params['configFiles']['Dockerfile']
        );
    }

    public function testUpdateDatastoreNoChangeLocalToLocal()
    {
        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->fromArray(['id' => 'Dockerfile']);
        $dockerfile->setName('Dockerfile')
            ->setSource('Dockerfile')
            ->setData($this->form->file['Dockerfile'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $build = new Entity\Docker\Service\Build();
        $build->setContext('build-context')
            ->setDockerfile('Dockerfile');

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData(['local']);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setTarget('datastore-target')
            ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addVolume($dockerfile)
            ->addVolume($serviceDatastoreVol)
            ->addMeta($dataStoreMeta)
            ->setBuild($build);

        $form = clone $this->form;
        $form->file['Dockerfile'] = 'new dockerfile data';

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([
                $this->seededPrivateNetworks['private-network-a'],
                $this->seededPrivateNetworks['private-network-b'],
            ]));

        $updatedService = $this->worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();
        $uDockerfileVol       = $updatedService->getVolume('Dockerfile');

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());
        $this->assertEquals($form->file['Dockerfile'], $uDockerfileVol->getData());

        $this->assertNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreNoChangeVolumeToVolume()
    {
        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->fromArray(['id' => 'Dockerfile']);
        $dockerfile->setName('Dockerfile')
            ->setSource('Dockerfile')
            ->setData($this->form->file['Dockerfile'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $build = new Entity\Docker\Service\Build();
        $build->setContext('build-context')
            ->setDockerfile('Dockerfile');

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData(['volume']);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setTarget('datastore-target')
            ->setType(Entity\Docker\ServiceVolume::TYPE_VOLUME);

        $projectDatastoreVol = new Entity\Docker\Volume();
        $projectDatastoreVol->setName('datastore')
            ->setProject($this->project)
            ->addServiceVolume($serviceDatastoreVol);

        $serviceDatastoreVol->setProjectVolume($projectDatastoreVol);

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addVolume($dockerfile)
            ->addVolume($serviceDatastoreVol)
            ->addMeta($dataStoreMeta)
            ->setBuild($build);

        $form = clone $this->form;
        $form->datastore = 'volume';

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([
                $this->seededPrivateNetworks['private-network-a'],
                $this->seededPrivateNetworks['private-network-b'],
            ]));

        $updatedService = $this->worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['volume'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_VOLUME, $uServiceDatastoreVol->getType());

        $this->assertNotNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreChangesLocalToVolume()
    {
        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->fromArray(['id' => 'Dockerfile']);
        $dockerfile->setName('Dockerfile')
            ->setSource('Dockerfile')
            ->setData($this->form->file['Dockerfile'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $build = new Entity\Docker\Service\Build();
        $build->setContext('build-context')
            ->setDockerfile('Dockerfile');

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData(['local']);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setTarget('datastore-target')
            ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addVolume($dockerfile)
            ->addVolume($serviceDatastoreVol)
            ->addMeta($dataStoreMeta)
            ->setBuild($build);

        $form = clone $this->form;
        $form->datastore = 'volume';

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([
                $this->seededPrivateNetworks['private-network-a'],
                $this->seededPrivateNetworks['private-network-b'],
            ]));

        $updatedService = $this->worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['volume'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_VOLUME, $uServiceDatastoreVol->getType());

        $this->assertNotNull($uProjectDatastoreVol);
    }

    public function testUpdateDatastoreChangesVolumeToLocal()
    {
        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->fromArray(['id' => 'Dockerfile']);
        $dockerfile->setName('Dockerfile')
            ->setSource('Dockerfile')
            ->setData($this->form->file['Dockerfile'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $build = new Entity\Docker\Service\Build();
        $build->setContext('build-context')
            ->setDockerfile('Dockerfile');

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData(['volume']);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setTarget('datastore-target')
            ->setType(Entity\Docker\ServiceVolume::TYPE_VOLUME);

        $projectDatastoreVol = new Entity\Docker\Volume();
        $projectDatastoreVol->setName('datastore')
            ->setProject($this->project)
            ->addServiceVolume($serviceDatastoreVol);

        $serviceDatastoreVol->setProjectVolume($projectDatastoreVol);

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addVolume($dockerfile)
            ->addVolume($serviceDatastoreVol)
            ->addMeta($dataStoreMeta)
            ->setBuild($build);

        $form = clone $this->form;
        $form->datastore = 'local';

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([
                $this->seededPrivateNetworks['private-network-a'],
                $this->seededPrivateNetworks['private-network-b'],
            ]));

        $updatedService = $this->worker->update($service, $form);

        $uDatastoreMeta       = $updatedService->getMeta('datastore');
        $uServiceDatastoreVol = $updatedService->getVolume('datastore');
        $uProjectDatastoreVol = $uServiceDatastoreVol->getProjectVolume();

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());

        $this->assertNull($uProjectDatastoreVol);
    }
}
