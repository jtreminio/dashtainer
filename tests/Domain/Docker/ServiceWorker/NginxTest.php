<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Nginx;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Repository;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NginxTest extends KernelTestCase
{
    /** @var Form\Docker\Service\NginxCreate */
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

    /** @var Nginx */
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

        $this->form = new Form\Docker\Service\NginxCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->server_name   = 'server_name';
        $this->form->server_alias  = ['server_alias'];
        $this->form->document_root = '~/www/project';
        $this->form->fcgi_handler  = 'php-fpm-7.2';
        $this->form->project_files = [
            'type'  => 'local',
            'local' => [
                'source' => '~/www/project',
            ]
        ];
        $this->form->vhost_conf    = <<<'EOD'
example
vhost
config
EOD;
        $this->form->system_file   = [
            'Dockerfile' => 'Dockerfile contents',
            'nginx.conf' => 'nginx.conf contents',
            'core.conf'  => 'core.conf contents',
            'proxy.conf' => 'proxy.conf contents',
        ];

        $this->worker = new Nginx($this->serviceRepo, $this->networkRepo, $this->serviceTypeRepo);

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

        $labels = $service->getLabels();

        $this->assertSame($this->form->name, $service->getName());
        $this->assertSame($this->form->type, $service->getType());
        $this->assertSame($this->form->project, $service->getProject());

        $build = $service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
        $this->assertEquals('Dockerfile', $build->getDockerfile());

        $expectedTraefikBackendLabel       = 'service-name';
        $expectedTraefikDockerNetworkLabel = 'traefik_webgateway';
        $expectedTraefikFrontendRuleLabel  = 'Host:server_name,server_alias';

        $this->assertEquals($expectedTraefikBackendLabel, $labels['traefik.backend']);
        $this->assertEquals(
            $expectedTraefikDockerNetworkLabel,
            $labels['traefik.docker.network']
        );
        $this->assertEquals(
            $expectedTraefikFrontendRuleLabel,
            $labels['traefik.frontend.rule']
        );

        $expectedVhostMeta = [
            'server_name'   => $this->form->server_name,
            'server_alias'  => $this->form->server_alias,
            'document_root' => $this->form->document_root,
            'fcgi_handler'  => $this->form->fcgi_handler,
        ];
        $vhostMeta = $service->getMeta('vhost');
        $this->assertEquals($expectedVhostMeta, $vhostMeta->getData());

        $this->assertNotNull($service->getVolume('Dockerfile'));
        $this->assertNotNull($service->getVolume('nginx.conf'));
        $this->assertNotNull($service->getVolume('core.conf'));
        $this->assertNotNull($service->getVolume('proxy.conf'));
        $this->assertNotNull($service->getVolume('vhost.conf'));

        $this->assertNotNull($service->getVolume('project_files_source'));
    }

    public function testGetCreateParams()
    {
        $phpfpmServiceType = new Entity\Docker\ServiceType();
        $phpfpmServices    = [
            new Entity\Docker\Service()
        ];

        $this->serviceTypeRepo->expects($this->once())
            ->method('findBySlug')
            ->with('php-fpm')
            ->will($this->returnValue($phpfpmServiceType));

        $this->serviceRepo->expects($this->once())
            ->method('findByProjectandType')
            ->with($this->project, $phpfpmServiceType)
            ->will($this->returnValue($phpfpmServices));

        $expected = [
            'fcgi_handlers' => [
                'phpfpm' => $phpfpmServices
            ],
        ];

        $this->assertEquals($expected, $this->worker->getCreateParams($this->project));
    }

    public function testGetViewParams()
    {
        $phpfpmServiceType = new Entity\Docker\ServiceType();
        $phpfpmServices    = [
            new Entity\Docker\Service()
        ];

        $this->serviceTypeRepo->expects($this->once())
            ->method('findBySlug')
            ->with('php-fpm')
            ->will($this->returnValue($phpfpmServiceType));

        $this->serviceRepo->expects($this->once())
            ->method('findByProjectandType')
            ->with($this->project, $phpfpmServiceType)
            ->will($this->returnValue($phpfpmServices));

        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertSame(
            $service->getVolume('Dockerfile'),
            $params['systemFiles']['Dockerfile']
        );
        $this->assertSame(
            $service->getVolume('nginx.conf'),
            $params['systemFiles']['nginx.conf']
        );
        $this->assertSame(
            $service->getVolume('core.conf'),
            $params['systemFiles']['core.conf']
        );
        $this->assertSame(
            $service->getVolume('proxy.conf'),
            $params['systemFiles']['proxy.conf']
        );
    }

    public function testUpdate()
    {
        $dockerfile = new Entity\Docker\ServiceVolume();
        $dockerfile->fromArray(['id' => 'Dockerfile']);
        $dockerfile->setName('Dockerfile')
            ->setSource('Dockerfile')
            ->setData($this->form->system_file['Dockerfile'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $nginxConf = new Entity\Docker\ServiceVolume();
        $nginxConf->fromArray(['id' => 'nginx.conf']);
        $nginxConf->setName('nginx.conf')
            ->setSource('nginx.conf')
            ->setTarget('/etc/nginx/nginx.conf')
            ->setData($this->form->system_file['nginx.conf'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $coreConf = new Entity\Docker\ServiceVolume();
        $coreConf->fromArray(['id' => 'core.conf']);
        $coreConf->setName('core.conf')
            ->setSource('core.conf')
            ->setTarget('/etc/nginx/core.conf')
            ->setData($this->form->system_file['core.conf'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $proxyConf = new Entity\Docker\ServiceVolume();
        $proxyConf->fromArray(['id' => 'proxy.conf']);
        $proxyConf->setName('proxy.conf')
            ->setSource('proxy.conf')
            ->setTarget('/etc/nginx/proxy.conf')
            ->setData($this->form->system_file['proxy.conf'])
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $vhostConf = new Entity\Docker\ServiceVolume();
        $vhostConf->fromArray(['id' => 'vhost.conf']);
        $vhostConf->setName('vhost.conf')
            ->setSource('vhost.conf')
            ->setTarget('/etc/nginx/sites-enabled/000-default.conf')
            ->setData($this->form->vhost_conf)
            ->setOwner(Entity\Docker\ServiceVolume::OWNER_SYSTEM);

        $build = new Entity\Docker\Service\Build();
        $build->setContext('build-context')
            ->setDockerfile('Dockerfile')
            ->setArgs([
                'SYSTEM_PACKAGES' => array_unique($this->form->system_packages),
            ]);

        $vhost = [
            'server_name'   => $this->form->server_name,
            'server_alias'  => $this->form->server_alias,
            'document_root' => $this->form->document_root,
            'fcgi_handler'  => $this->form->fcgi_handler,
        ];

        $vhostMeta = new Entity\Docker\ServiceMeta();
        $vhostMeta->setName('vhost')
            ->setData($vhost);

        $projectFilesMeta = new Entity\Docker\ServiceMeta();
        $projectFilesMeta->setName('project_files')
            ->setData($vhost);

        $service = new Entity\Docker\Service();
        $service->setName($this->form->name)
            ->setType($this->serviceType)
            ->setProject($this->project)
            ->addNetwork($this->publicNetwork)
            ->addNetwork($this->seededPrivateNetworks['private-network-a'])
            ->addNetwork($this->seededPrivateNetworks['private-network-b'])
            ->addLabel('traefik.backend', $service->getName())
            ->addLabel('traefik.docker.network', 'traefik_webgateway')
            ->addLabel('traefik.frontend.rule', 'frontend_rule')
            ->addVolume($dockerfile)
            ->addVolume($nginxConf)
            ->addVolume($coreConf)
            ->addVolume($proxyConf)
            ->addVolume($vhostConf)
            ->addMeta($vhostMeta)
            ->addMeta($projectFilesMeta)
            ->setBuild($build);

        $form = new Form\Docker\Service\NginxCreate();
        $form->project = $this->project;
        $form->type    = $this->serviceType;
        $form->name    = 'service-name';

        $form->system_packages = ['systemPackageA'];

        $form->server_name   = 'updatedServerName';
        $form->server_alias  = ['aliasA', 'aliasB'];
        $form->document_root = '/path/to/glory';
        $form->fcgi_handler  = '';

        $form->system_file['Dockerfile'] = 'new dockerfile data';
        $form->system_file['nginx.conf'] = 'new nginx.conf data';
        $form->system_file['core.conf']  = 'new core.conf data';
        $form->system_file['proxy.conf'] = 'new proxy.conf data';
        $form->vhost_conf                = 'new vhost.conf data';
        $form->project_files             = [
            'type'  => 'local',
            'local' => [ 'source' => '/path/to/glory' ]
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

        $updatedBuild = $updatedService->getBuild();

        $this->assertEquals($form->system_packages, $updatedBuild->getArgs()['SYSTEM_PACKAGES']);

        $this->assertEquals(
            'Host:updatedServerName,aliasA,aliasB',
            $updatedService->getLabels()['traefik.frontend.rule']
        );

        $fileDockerFile = $updatedService->getVolume('Dockerfile');
        $uNginxConf     = $updatedService->getVolume('nginx.conf');
        $uCoreConf      = $updatedService->getVolume('core.conf');
        $uProxyConf     = $updatedService->getVolume('proxy.conf');
        $fileVhost      = $updatedService->getVolume('vhost.conf');

        $this->assertEquals($form->system_file['Dockerfile'], $fileDockerFile->getData());
        $this->assertEquals($form->system_file['nginx.conf'], $uNginxConf->getData());
        $this->assertEquals($form->system_file['core.conf'], $uCoreConf->getData());
        $this->assertEquals($form->system_file['proxy.conf'], $uProxyConf->getData());
        $this->assertEquals($form->vhost_conf, $fileVhost->getData());
    }
}
