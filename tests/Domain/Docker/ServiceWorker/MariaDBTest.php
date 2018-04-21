<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\MariaDB;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MariaDBTest extends KernelTestCase
{
    /** @var Form\Docker\Service\MariaDBCreate */
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

    /** @var MariaDB */
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

        $this->form = new Form\Docker\Service\MariaDBCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->system_file = [
            'my.cnf'          => 'my.cnf contents',
            'config-file.cnf' => 'config-file.cnf contents',
        ];
        $this->form->datastore   = 'local';
        $this->form->version     = '1.2';

        $this->form->port         = null;
        $this->form->port_confirm = false;
        $this->form->port_used    = false;

        $this->form->mysql_root_password = 'rootpw';
        $this->form->mysql_database      = 'dbname';
        $this->form->mysql_user          = 'dbuser';
        $this->form->mysql_password      = 'userpw';

        $this->worker = new MariaDB($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);

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

        $this->assertEquals('rootpw', $environment['MYSQL_ROOT_PASSWORD']);
        $this->assertEquals('dbname', $environment['MYSQL_DATABASE']);
        $this->assertEquals('dbuser', $environment['MYSQL_USER']);
        $this->assertEquals('userpw', $environment['MYSQL_PASSWORD']);

        $myCnfVolume      = $service->getVolume('my.cnf');
        $configFileVolume = $service->getVolume('config-file.cnf');

        $this->assertNotNull($myCnfVolume);
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
            3307
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([3307]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([3308]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([3309]),
            ],
            3310
        ];

        yield [
            [
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([3307]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([3309]),
                (new Entity\Docker\ServiceMeta())->setName('bind-port')->setData([3311]),
            ],
            3308
        ];
    }

    public function testGetViewParams()
    {
        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertSame(
            $service->getVolume('my.cnf'),
            $params['systemFiles']['my.cnf']
        );
        $this->assertSame(
            $service->getVolume('config-file.cnf'),
            $params['systemFiles']['config-file.cnf']
        );

        $this->assertEquals($this->form->version, $params['version']);
        $this->assertEquals(3307, $params['bindPort']);
        $this->assertEquals($this->form->port_confirm, $params['portConfirm']);
        $this->assertEquals($this->form->mysql_root_password, $params['mysql_root_password']);
        $this->assertEquals($this->form->mysql_database, $params['mysql_database']);
        $this->assertEquals($this->form->mysql_user, $params['mysql_user']);
        $this->assertEquals($this->form->mysql_password, $params['mysql_password']);
    }

    public function testUpdate()
    {
        $myCnfVol = new Entity\Docker\ServiceVolume();
        $myCnfVol->fromArray(['id' => 'my.cnf']);
        $myCnfVol->setName('my.cnf')
            ->setSource('my.cnf')
            ->setData($this->form->system_file['my.cnf'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM)
            ->setFiletype(Entity\Docker\ServiceVolume::FILETYPE_FILE);

        $configFileVol = new Entity\Docker\ServiceVolume();
        $configFileVol->fromArray(['id' => 'config-file.cnf']);
        $configFileVol->setName('config-file.cnf')
            ->setSource('config-file.cnf')
            ->setData($this->form->system_file['config-file.cnf'])
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
            'MYSQL_ROOT_PASSWORD' => $this->form->mysql_root_password,
            'MYSQL_DATABASE'      => $this->form->mysql_database,
            'MYSQL_USER'          => $this->form->mysql_user,
            'MYSQL_PASSWORD'      => $this->form->mysql_password,
        ];

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->setImage('mariadb:1.2')
            ->setRestart(Entity\Docker\Service::RESTART_ALWAYS)
            ->setEnvironments($environments)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addVolume($myCnfVol)
            ->addVolume($configFileVol)
            ->addVolume($serviceDatastoreVol)
            ->addMeta($dataStoreMeta)
            ->addMeta($versionMeta)
            ->addMeta($portMeta);

        $form = clone $this->form;
        $form->mysql_root_password = 'newrootpw';
        $form->mysql_database      = 'newdb';
        $form->mysql_user          = 'newuser';
        $form->mysql_password      = 'newuserpw';

        $form->port_confirm = true;
        $form->port         = 3307;

        $form->system_file['my.cnf']          = 'new_mycnf data';
        $form->system_file['config-file.cnf'] = 'new configfile data';

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

        $uMyCnfVol      = $updatedService->getVolume('my.cnf');
        $uConfigFileVol = $updatedService->getVolume('config-file.cnf');
        $uPortMeta      = $updatedService->getMeta('bind-port');
        $uServicePorts  = $updatedService->getPorts();
        $uEnvironments  = $updatedService->getEnvironments();

        $expectedEnvironments = [
            'MYSQL_ROOT_PASSWORD' => $form->mysql_root_password,
            'MYSQL_DATABASE'      => $form->mysql_database,
            'MYSQL_USER'          => $form->mysql_user,
            'MYSQL_PASSWORD'      => $form->mysql_password,
        ];

        $expectedServicePorts = ["{$form->port}:3306"];

        $this->assertEquals($form->system_file['my.cnf'], $uMyCnfVol->getData());
        $this->assertEquals($form->system_file['config-file.cnf'], $uConfigFileVol->getData());
        $this->assertEquals([$form->port], $uPortMeta->getData());
        $this->assertEquals($expectedServicePorts, $uServicePorts);
        $this->assertEquals($expectedEnvironments, $uEnvironments);
    }
}
