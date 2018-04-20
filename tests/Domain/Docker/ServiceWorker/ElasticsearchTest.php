<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Elasticsearch;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ElasticsearchTest extends KernelTestCase
{
    /** @var Form\Docker\Service\ElasticsearchCreate */
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

    /** @var Elasticsearch */
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

        $this->form = new Form\Docker\Service\ElasticsearchCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'elasticsearch.yml' => 'elasticsearch.yml contents',
        ];
        $this->form->datastore   = 'local';
        $this->form->version     = '1.2';
        $this->form->heap_size   = '1m';

        $this->worker = new Elasticsearch($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);

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

        $environment = $service->getEnvironments();

        $this->assertEquals(
            'docker.elastic.co/elasticsearch/elasticsearch-oss:1.2',
            $service->getImage()
        );

        $this->assertEquals('-Xms1m -Xmx1m', $environment['ES_JAVA_OPTS']);

        $datastoreVolume = $service->getVolume('datastore');
        $esVolume        = $service->getVolume('elasticsearch.yml');

        $this->assertNotNull($esVolume);
        $this->assertNotNull($datastoreVolume);
        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($service->getMeta('version'));
        $this->assertNotNull($service->getMeta('heap_size'));

        $this->assertEquals('$PWD/service-name/datadir', $datastoreVolume->getSource());
        $this->assertEquals($this->form->system_file['elasticsearch.yml'], $esVolume->getData());
    }

    public function testGetCreateParams()
    {
        $this->assertEquals([], $this->worker->getCreateParams($this->project));
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals($this->form->datastore, $params['datastore']);
        $this->assertEquals($this->form->heap_size, $params['heap_size']);
        $this->assertSame(
            $service->getVolume('elasticsearch.yml'),
            $params['systemFiles']['elasticsearch.yml']
        );
    }

    public function testUpdate()
    {
        $esVol = new Entity\Docker\ServiceVolume();
        $esVol->fromArray(['id' => 'elasticsearch.yml']);
        $esVol->setName('elasticsearch.yml')
            ->setSource('elasticsearch.yml')
            ->setData($this->form->system_file['elasticsearch.yml'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE);

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData(['local']);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$this->form->version]);

        $heapsizeMeta = new Entity\Docker\ServiceMeta();
        $heapsizeMeta->setName('heap_size')
            ->setData([$this->form->heap_size]);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setTarget('datastore-target')
            ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

        $environments = [
            'ES_JAVA_OPTS' => "-Xms{$this->form->heap_size} -Xmx{$this->form->heap_size}",
        ];

        $ulimits = new Entity\Docker\Service\Ulimits();
        $ulimits->setMemlock(-1, -1);

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->setImage('docker.elastic.co/elasticsearch/elasticsearch-oss:1.2')
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS)
            ->setEnvironments($environments)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addVolume($esVol)
            ->addVolume($serviceDatastoreVol)
            ->addMeta($dataStoreMeta)
            ->addMeta($versionMeta)
            ->addMeta($heapsizeMeta)
            ->setUlimits($ulimits);

        $form = clone $this->form;
        $form->system_file['elasticsearch.yml'] = 'new elasticsearch.yml data';
        $form->heap_size = '5m';

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
        $uEsVol               = $updatedService->getVolume('elasticsearch.yml');
        $uHeapsizeMeta        = $updatedService->getMeta('heap_size');
        $uEnvironments        = $updatedService->getEnvironments();

        $expectedEnvironments = [
            'ES_JAVA_OPTS' => "-Xms5m -Xmx5m",
        ];

        $this->assertEquals(['local'], $uDatastoreMeta->getData());
        $this->assertEquals(['5m'], $uHeapsizeMeta->getData());
        $this->assertEquals(Entity\Docker\ServiceVolume::TYPE_BIND, $uServiceDatastoreVol->getType());
        $this->assertEquals($form->system_file['elasticsearch.yml'], $uEsVol->getData());
        $this->assertEquals($expectedEnvironments, $uEnvironments);
    }
}
