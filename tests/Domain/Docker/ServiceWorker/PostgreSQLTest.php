<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\PostgreSQL;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostgreSQLTest extends KernelTestCase
{
    /** @var Form\Docker\Service\PostgreSQLCreate */
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

    /** @var PostgreSQL */
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

        $this->form = new Form\Docker\Service\PostgreSQLCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'postgresql.conf' => 'postgresql.conf contents',
        ];
        $this->form->datastore   = 'local';
        $this->form->version     = '1.2';

        $this->form->port         = null;
        $this->form->port_confirm = false;
        $this->form->port_used    = false;

        $this->form->postgres_db       = 'dbname';
        $this->form->postgres_user     = 'dbuser';
        $this->form->postgres_password = 'userpw';

        $this->worker = new PostgreSQL($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);

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

        $this->assertEquals('dbname', $environment['POSTGRES_DB']);
        $this->assertEquals('dbuser', $environment['POSTGRES_USER']);
        $this->assertEquals('userpw', $environment['POSTGRES_PASSWORD']);

        $configFileVolume = $service->getVolume('postgresql.conf');

        $this->assertNotNull($configFileVolume);
        $this->assertNotNull($service->getMeta('datastore'));
        $this->assertNotNull($service->getMeta('version'));
        $this->assertNotNull($service->getMeta('bind-port'));
    }

    /**
     * @var array $usedPorts
     * @var int   $openPort
     * @dataProvider providerGetCreateParamsReturnsFirstUnusedBindPort
     */
    public function testGetCreateParamsReturnsFirstUnusedBindPort(array $usedPorts, int $openPort)
    {
        $service = $this->worker->create($this->form);

        $this->serviceRepo->expects($this->once())
            ->method('getProjectBindPorts')
            ->with($this->project)
            ->will($this->returnValue($usedPorts));

        $params = $this->worker->getViewParams($service);

        $this->assertEquals($openPort, $params['bindPort']);
    }

    public function providerGetCreateParamsReturnsFirstUnusedBindPort()
    {
        yield [
            [],
            5433
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([5433]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([5434]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([5435]),
            ],
            5436
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([5433]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([5435]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([5437]),
            ],
            5434
        ];
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertSame(
            $service->getVolume('postgresql.conf'),
            $params['systemFiles']['postgresql.conf']
        );

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals(5433, $params['bindPort']);
        $this->assertEquals($this->form->port_confirm, $params['portConfirm']);
        $this->assertEquals($this->form->postgres_db, $params['postgres_db']);
        $this->assertEquals($this->form->postgres_user, $params['postgres_user']);
        $this->assertEquals($this->form->postgres_password, $params['postgres_password']);
    }

    public function testUpdate()
    {
        $configFileVol = new Entity\Docker\ServiceVolume();
        $configFileVol->fromArray(['id' => 'postgresql.conf']);
        $configFileVol->setName('postgresql.conf')
            ->setSource('postgresql.conf')
            ->setData($this->form->system_file['postgresql.conf'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE);

        $serviceDatastoreVol = new Entity\Docker\ServiceVolume();
        $serviceDatastoreVol->setName('datastore')
            ->setTarget('datastore-target')
            ->setType(Entity\Docker\ServiceVolume::TYPE_BIND);

        $dataStoreMeta = new Entity\Docker\ServiceMeta();
        $dataStoreMeta->setName('datastore')
            ->setData(['local']);

        $versionMeta = new Entity\Docker\ServiceMeta();
        $versionMeta->setName('version')
            ->setData([$this->form->version]);

        $portMeta = new Entity\Docker\ServiceMeta();
        $portMeta->setName('bind-port')
            ->setData([]);

        $environments = [
            'POSTGRES_DB'       => $this->form->postgres_db,
            'POSTGRES_USER'     => $this->form->postgres_user,
            'POSTGRES_PASSWORD' => $this->form->postgres_password,
        ];

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->setImage('postgresql:1.2')
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS)
            ->setEnvironments($environments)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addVolume($configFileVol)
            ->addVolume($serviceDatastoreVol)
            ->addMeta($dataStoreMeta)
            ->addMeta($versionMeta)
            ->addMeta($portMeta);

        $form = clone $this->form;
        $form->postgres_db       = 'newdb';
        $form->postgres_user     = 'newuser';
        $form->postgres_password = 'newuserpw';

        $form->port_confirm = true;
        $form->port         = 3307;

        $form->system_file['postgresql.conf'] = 'new configfile data';

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

        $uConfigFileVol = $updatedService->getVolume('postgresql.conf');
        $uPortMeta      = $updatedService->getMeta('bind-port');
        $uServicePorts  = $updatedService->getPorts();
        $uEnvironments  = $updatedService->getEnvironments();

        $expectedEnvironments = [
            'POSTGRES_DB'       => $form->postgres_db,
            'POSTGRES_USER'     => $form->postgres_user,
            'POSTGRES_PASSWORD' => $form->postgres_password,
        ];

        $expectedServicePorts = ["{$form->port}:5432"];

        $this->assertEquals($form->system_file['postgresql.conf'], $uConfigFileVol->getData());
        $this->assertEquals([$form->port], $uPortMeta->getData());
        $this->assertEquals($expectedServicePorts, $uServicePorts);
        $this->assertEquals($expectedEnvironments, $uEnvironments);
    }
}
