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
    /** @var Adminer */
    protected $adminer;

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

        $this->adminer = new Adminer($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);

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

        $service = $this->adminer->create($this->form);

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

        $service = $this->adminer->create($this->form);

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

        $service = $this->adminer->create($this->form);

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

        $service = $this->adminer->create($this->form);

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

    public function testCreateReturnsServiceEntityWithNoCustomFiles()
    {
        $this->form->custom_file = [];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue([]));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->adminer->create($this->form);

        $this->assertEmpty($service->getVolumes());
    }

    public function testCreateReturnsServiceEntityWithCustomFiles()
    {
        $customFileA = [
            'filename' => 'custom file a.txt',
            'target'   => '/etc/foo/bar',
            'data'     => 'you are awesome!',
        ];

        $customFileB = [
            'filename' => 'custom file b.txt',
            'target'   => '/etc/foo/bam',
            'data'     => 'everyone admires you!',
        ];

        $this->form->custom_file = [$customFileA, $customFileB];

        $this->networkRepo->expects($this->once())
            ->method('getPrivateNetworks')
            ->with($this->form->project)
            ->will($this->returnValue([]));

        $this->networkRepo->expects($this->once())
            ->method('findByService')
            ->will($this->returnValue([]));

        $service = $this->adminer->create($this->form);

        $fileA = $service->getVolume('customfilea.txt');
        $fileB = $service->getVolume('customfileb.txt');

        $expectedSourceA = '$PWD/service-name/customfilea.txt';
        $this->assertEquals($expectedSourceA, $fileA->getSource());
        $this->assertEquals($customFileA['target'], $fileA->getTarget());
        $this->assertEquals($customFileA['data'], $fileA->getData());

        $expectedSourceA = '$PWD/service-name/customfileb.txt';
        $this->assertEquals($expectedSourceA, $fileB->getSource());
        $this->assertEquals($customFileB['target'], $fileB->getTarget());
        $this->assertEquals($customFileB['data'], $fileB->getData());
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

        $service = $this->adminer->create($this->form);

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
        $this->assertEquals([], $this->adminer->getCreateParams($this->project));
    }

    public function testGetViewParams()
    {
        $customFileA = [
            'filename' => 'custom file a.txt',
            'target'   => '/etc/foo/bar',
            'data'     => 'you are awesome!',
        ];

        $this->form->custom_file = [$customFileA];

        $service = $this->adminer->create($this->form);
        $params = $this->adminer->getViewParams($service);

        $this->assertEquals($this->form->design, $params['design']);
        $this->assertEquals($this->form->plugins, $params['plugins']);
        $this->assertEquals(['designA', 'designB'], $params['availableDesigns']);
        $this->assertEquals(['pluginA', 'pluginB'], $params['availablePlugins']);

        $this->assertCount(1, $params['customFiles']);

        $this->assertSame(
            $service->getVolume('customfilea.txt'),
            array_pop($params['customFiles'])
        );
    }

    public function testUpdate()
    {
        $this->seedProjectWithPrivateNetworks();

        $customFileA = new Entity\Docker\ServiceVolume();
        $customFileA->fromArray(['id' => 'customfilea_ID']);
        $customFileA->setName('customfilea.txt')
            ->setSource('$PWD/service-name/customfilea.txt')
            ->setTarget('/etc/foo/bar')
            ->setData('you are awesome!')
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_USER);

        $customFileB = new Entity\Docker\ServiceVolume();
        $customFileB->fromArray(['id' => 'customfileb_ID']);
        $customFileB->setName('customfileb.txt')
            ->setSource('$PWD/service-name/customfileb.txt')
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
            ->addVolume($customFileA)
            ->addVolume($customFileB);

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

        $form->custom_file = [
            'customfilea_ID' => [
                'filename' => 'custom file a_updated.txt',
                'target'   => '/etc/foo/updated/path',
                'data'     => 'updated text!',
            ],
            'customfilec_ID' => [
                'filename' => 'custom file c.txt',
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

        $updatedService = $this->adminer->update($service, $form);

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

        $fileA = $updatedService->getVolume('customfilea_updated.txt');
        $fileC = $updatedService->getVolume('customfilec.txt');

        $this->assertEquals('/etc/foo/updated/path', $fileA->getTarget());
        $this->assertEquals('updated text!', $fileA->getData());

        $this->assertEquals('/etc/foo/new/file', $fileC->getTarget());
        $this->assertEquals('new file!', $fileC->getData());

        $this->assertNull($updatedService->getVolume('customfileb.txt'));
    }
}
