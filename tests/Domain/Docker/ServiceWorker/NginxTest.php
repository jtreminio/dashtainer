<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Nginx;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;

class NginxTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\NginxCreate */
    protected $form;

    /** @var Nginx */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

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
    }

    public function testCreateReturnsServiceEntity()
    {
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
            ->method('findByProjectAndType')
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
            ->method('findByProjectAndType')
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
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

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
