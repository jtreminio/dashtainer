<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Adminer;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdminerTest extends KernelTestCase
{
    /** @var Form\Docker\Service\AdminerCreate */
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

    /** @var Adminer */
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

        $availableDesignsMeta = new Entity\Docker\ServiceTypeMeta();
        $availableDesignsMeta->setName('designs')
            ->setData([
                'default'   => ['designA'],
                'available' => ['designA', 'designB'],
            ]);

        $availablePluginsMeta = new Entity\Docker\ServiceTypeMeta();
        $availablePluginsMeta->setName('plugins')
            ->setData([
                'available' => ['pluginA', 'pluginB'],
            ]);

        $this->serviceType->addMeta($availableDesignsMeta)
            ->addMeta($availablePluginsMeta);

        $this->form = new Form\Docker\Service\AdminerCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';
        $this->form->design  = 'form-design';

        $this->worker = new Adminer($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);

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

    public function testCreateReturnsServiceEntityWithNoPrivateNetworks()
    {
        $this->seedProjectWithPrivateNetworks();

        $this->form->networks = [];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->worker->create($this->form);

        $this->assertCount(1, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithNewAndExistingPrivateNetworks()
    {
        $this->seedProjectWithPrivateNetworks();

        $this->form->networks = [
            'new-network-a',
            'new-network-b',
            'private-network-a',
        ];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->worker->create($this->form);

        $networks = [];
        foreach ($service->getNetworks() as $network) {
            $networks []= $network->getName();
        }

        $this->assertCount(4, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());
        $this->assertContains('new-network-a', $networks);
        $this->assertContains('new-network-b', $networks);
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithNewPrivateNetworks()
    {
        $this->seedProjectWithPrivateNetworks();

        $this->form->networks = [
            'new-network-a',
            'new-network-b',
        ];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->worker->create($this->form);

        $networks = [];
        foreach ($service->getNetworks() as $network) {
            $networks []= $network->getName();
        }

        $this->assertCount(3, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());
        $this->assertContains('new-network-a', $networks);
        $this->assertContains('new-network-b', $networks);

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithExistingPrivateNetworks()
    {
        $this->seedProjectWithPrivateNetworks();

        $this->form->networks = [
            'private-network-a',
        ];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue($this->seededPrivateNetworks));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->worker->create($this->form);

        $this->assertCount(2, $service->getNetworks());
        $this->assertContains($this->publicNetwork, $service->getNetworks());
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-a'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $service->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-c'],
            $service->getNetworks()
        );
    }

    public function testCreateReturnsServiceEntityWithNoUserFiles()
    {
        $this->form->user_file = [];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue([]));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->worker->create($this->form);

        $this->assertEmpty($service->getVolumes());
    }

    public function testCreateReturnsServiceEntityWithUserFiles()
    {
        $userFileA = [
            'filename' => 'user file a.txt',
            'target'   => '/etc/foo/bar',
            'data'     => 'you are awesome!',
        ];

        $userFileB = [
            'filename' => 'user file b.txt',
            'target'   => '/etc/foo/bam',
            'data'     => 'everyone admires you!',
        ];

        $this->form->user_file = [$userFileA, $userFileB];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue([]));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->worker->create($this->form);

        $fileA = $service->getVolume('userfilea.txt');
        $fileB = $service->getVolume('userfileb.txt');

        $expectedSourceA = '$PWD/service-name/userfilea.txt';
        $this->assertEquals($expectedSourceA, $fileA->getSource());
        $this->assertEquals($userFileA['target'], $fileA->getTarget());
        $this->assertEquals($userFileA['data'], $fileA->getData());

        $expectedSourceA = '$PWD/service-name/userfileb.txt';
        $this->assertEquals($expectedSourceA, $fileB->getSource());
        $this->assertEquals($userFileB['target'], $fileB->getTarget());
        $this->assertEquals($userFileB['data'], $fileB->getData());
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

        $labels = $service->getLabels();

        $this->assertSame($this->form->name, $service->getName());
        $this->assertSame($this->form->type, $service->getType());
        $this->assertSame($this->form->project, $service->getProject());

        $this->assertEquals('adminer', $service->getImage());

        $expectedTraefikBackendLabel       = 'service-name';
        $expectedTraefikDockerNetworkLabel = 'traefik_webgateway';
        $expectedTraefikFrontendRuleLabel  = 'Host:service-name.projectname.localhost';
        $this->assertEquals($expectedTraefikBackendLabel, $labels['traefik.backend']);
        $this->assertEquals(
            $expectedTraefikDockerNetworkLabel,
            $labels['traefik.docker.network']
        );
        $this->assertEquals(
            $expectedTraefikFrontendRuleLabel,
            $labels['traefik.frontend.rule']
        );
    }

    public function testGetCreateParams()
    {
        $this->assertEquals([], $this->worker->getCreateParams($this->project));
    }

    public function testGetViewParams()
    {
        $userFileA = [
            'filename' => 'user file a.txt',
            'target'   => '/etc/foo/bar',
            'data'     => 'you are awesome!',
        ];

        $this->form->user_file = [$userFileA];

        $service = $this->worker->create($this->form);
        $params = $this->worker->getViewParams($service);

        $this->assertEquals($this->form->design, $params['design']);
        $this->assertEquals($this->form->plugins, $params['plugins']);
        $this->assertEquals(['designA', 'designB'], $params['availableDesigns']);
        $this->assertEquals(['pluginA', 'pluginB'], $params['availablePlugins']);

        $this->assertCount(1, $params['userFiles']);

        $this->assertSame(
            $service->getVolume('userfilea.txt'),
            array_pop($params['userFiles'])
        );
    }

    public function testUpdate()
    {
        $this->seedProjectWithPrivateNetworks();

        $userFileA = new Entity\Docker\ServiceVolume();
        $userFileA->fromArray(['id' => 'userfilea_ID']);
        $userFileA->setName('userfilea.txt')
            ->setSource('$PWD/service-name/userfilea.txt')
            ->setTarget('/etc/foo/bar')
            ->setData('you are awesome!')
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_USER);

        $userFileB = new Entity\Docker\ServiceVolume();
        $userFileB->fromArray(['id' => 'userfileb_ID']);
        $userFileB->setName('userfileb.txt')
            ->setSource('$PWD/service-name/userfileb.txt')
            ->setTarget('/etc/foo/bam')
            ->setData('everyone admires you!')
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_USER);

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->setImage('adminer')
            ->setEnvironments([
                'ADMINER_DESIGN'  => $this->form->design,
                'ADMINER_PLUGINS' => join(' ', $this->form->plugins),
            ])
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addLabel('traefik.backend', $service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', 'frontend_rule')
            ->addVolume($userFileA)
            ->addVolume($userFileB);

        $form = new Form\Docker\Service\AdminerCreate();
        $form->project = $this->project;
        $form->type    = $this->serviceType;
        $form->name    = 'service-name';

        $form->design = 'new-design-choice';
        $form->plugins = ['new-plugin-a', 'new-plugin-b'];
        $form->networks = [
            'private-network-a',
            'private-network-c',
            'new-network-a',
        ];

        $form->user_file = [
            'userfilea_ID' => [
                'filename' => 'user file a_updated.txt',
                'target'   => '/etc/foo/updated/path',
                'data'     => 'updated text!',
            ],
            'userfilec_ID' => [
                'filename' => 'user file c.txt',
                'target'   => '/etc/foo/new/file',
                'data'     => 'new file!',
            ],
        ];

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

        $environments = $updatedService->getEnvironments();

        $this->assertEquals('new-design-choice', $environments['ADMINER_DESIGN']);
        $this->assertEquals('new-plugin-a new-plugin-b', $environments['ADMINER_PLUGINS']);

        $networks = [];
        foreach ($updatedService->getNetworks() as $network) {
            $networks []= $network->getName();
        }

        $this->assertCount(4, $updatedService->getNetworks());
        $this->assertContains($this->publicNetwork, $updatedService->getNetworks());
        $this->assertContains('new-network-a', $networks);
        $this->assertContains(
            $this->seededPrivateNetworks['private-network-a'],
            $updatedService->getNetworks()
        );

        $this->assertContains(
            $this->seededPrivateNetworks['private-network-c'],
            $updatedService->getNetworks()
        );

        $this->assertNotContains(
            $this->seededPrivateNetworks['private-network-b'],
            $updatedService->getNetworks()
        );

        $fileA = $updatedService->getVolume('userfilea_updated.txt');
        $fileC = $updatedService->getVolume('userfilec.txt');

        $this->assertEquals('/etc/foo/updated/path', $fileA->getTarget());
        $this->assertEquals('updated text!', $fileA->getData());

        $this->assertEquals('/etc/foo/new/file', $fileC->getTarget());
        $this->assertEquals('new file!', $fileC->getData());

        $this->assertNull($updatedService->getVolume('userfileb.txt'));
    }
}
