<?php

namespace Dashtainer\Tests\Domain\Docker\ServiceWorker;

use Dashtainer\Domain\Docker\ServiceWorker\Apache;
use Dashtainer\Entity;
use Dashtainer\Form;
use Dashtainer\Tests\Domain\Docker\ServiceWorkerBase;
use Dashtainer\Tests\Mock\RepoDockerService;

class ApacheTest extends ServiceWorkerBase
{
    /** @var Form\Docker\Service\ApacheCreate */
    protected $form;

    /** @var Apache */
    protected $worker;

    protected function setUp()
    {
        parent::setUp();

        $phpfpmServiceType = new Entity\Docker\ServiceType();
        $phpfpmServiceType->setName('php-fpm')
            ->setSlug('php-fpm');

        $nodeJsServiceType = new Entity\Docker\ServiceType();
        $nodeJsServiceType->setName('node-js')
            ->setSlug('node-js');

        $this->serviceTypeRepo->addServiceType($phpfpmServiceType);
        $this->serviceTypeRepo->addServiceType($nodeJsServiceType);

        $this->serviceRepo = new RepoDockerService($this->em);

        $moduleMeta = new Entity\Docker\ServiceTypeMeta();
        $moduleMeta->setName('modules')
            ->setData([
                'default'   => ['default_data'],
                'available' => ['available_data']
            ]);

        $this->serviceType->addMeta($moduleMeta);

        $this->form = new Form\Docker\Service\ApacheCreate();
        $this->form->project = $this->project;
        $this->form->type    = $this->serviceType;
        $this->form->name    = 'service-name';

        $this->form->enabled_modules  = ['mpm_event', 'proxy_fcgi', 'rewrite'];
        $this->form->disabled_modules = ['mpm_prefork', 'mpm_worker', 'dupe', 'dupe'];
        $this->form->server_name      = 'server_name';
        $this->form->server_alias     = ['server_alias'];
        $this->form->document_root    = '~/www/project';
        $this->form->handler          = 'php-fpm-7.2:9000';

        $this->form->project_files = [
            'type'  => 'local',
            'local' => [
                'source' => '~/www/project',
            ]
        ];
        $this->form->vhost_conf = <<<'EOD'
example
vhost
config
EOD;
        $this->form->system_file = [
            'Dockerfile'   => 'Dockerfile contents',
            'apache2.conf' => 'apache2.conf contents',
            'ports.conf'   => 'ports.conf contents',
        ];

        $this->worker = new Apache(
            $this->serviceRepo,
            $this->networkRepo,
            $this->serviceTypeRepo,
            $this->secretDomain
        );
    }

    public function testCreateReturnsServiceEntity()
    {
        $service = $this->worker->create($this->form);

        $labels = $service->getLabels();

        $expectedModulesDisabled = ['mpm_prefork', 'mpm_worker', 'dupe'];
        $build = $service->getBuild();
        $this->assertEquals('./service-name', $build->getContext());
        $this->assertEquals('Dockerfile', $build->getDockerfile());
        $this->assertEquals($expectedModulesDisabled, $build->getArgs()['APACHE_MODULES_DISABLE']);

        $expectedTraefikBackendLabel       = '{$COMPOSE_PROJECT_NAME}_service-name';
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
            'handler'       => $this->form->handler,
        ];
        $vhostMeta = $service->getMeta('vhost');
        $this->assertEquals($expectedVhostMeta, $vhostMeta->getData());

        $this->assertNotNull($service->getVolume('Dockerfile'));
        $this->assertNotNull($service->getVolume('apache2.conf'));
        $this->assertNotNull($service->getVolume('ports.conf'));
        $this->assertNotNull($service->getVolume('vhost.conf'));
        $this->assertNotNull($service->getVolume('project_files_source'));
    }

    public function testGetCreateParams()
    {
        $phpFpmService = new Entity\Docker\Service();
        $phpFpmService->setName('php-fpm');

        $phpfpmServiceType = new Entity\Docker\ServiceType();
        $phpfpmServiceType->setName('php-fpm')
            ->setSlug('php-fpm');

        $phpFpmService->setType($phpfpmServiceType);
        $this->project->addService($phpFpmService);

        $expected = [
            'handlers' => [
                'PHP-FPM' => [$phpFpmService->getSlug() . ':9000'],
                'Node.js' => [],
            ],
        ];

        $result = $this->worker->getCreateParams($this->project);

        $this->assertEquals($expected['handlers'], $result['handlers']);
    }

    public function testGetViewParams()
    {
        $phpFpmService = new Entity\Docker\Service();
        $phpFpmService->setName('php-fpm');

        $phpfpmServiceType = new Entity\Docker\ServiceType();
        $phpfpmServiceType->setName('php-fpm')
            ->setSlug('fcgi');

        $phpFpmService->setType($phpfpmServiceType);
        $this->project->addService($phpFpmService);

        $userFileA = [
            'filename' => 'user file a.txt',
            'target'   => '/etc/foo/bar',
            'data'     => 'you are awesome!',
        ];

        $this->form->user_file = [$userFileA];

        $service = $this->worker->create($this->form);
        $params  = $this->worker->getViewParams($service);

        $this->assertSame(
            $service->getVolume('Dockerfile'),
            $params['systemFiles']['Dockerfile']
        );
        $this->assertSame(
            $service->getVolume('apache2.conf'),
            $params['systemFiles']['apache2.conf']
        );
        $this->assertSame(
            $service->getVolume('ports.conf'),
            $params['systemFiles']['ports.conf']
        );

        $this->assertSame(
            $service->getVolume('userfilea.txt'),
            array_pop($params['userFiles'])
        );
    }

    public function testUpdate()
    {
        $service = $this->worker->create($this->form);

        $form = clone $this->form;

        $form->system_packages  = ['systemPackageA'];
        $form->enabled_modules  = ['enabledModuleA'];
        $form->disabled_modules = ['disabledModuleA'];

        $form->server_name   = 'updatedServerName';
        $form->server_alias  = ['aliasA', 'aliasB'];
        $form->document_root = '/path/to/glory';
        $form->handler       = '';

        $form->system_file['Dockerfile']   = 'new dockerfile data';
        $form->system_file['apache2.conf'] = 'new apache2.conf data';
        $form->system_file['ports.conf']   = 'new ports.conf data';

        $form->vhost_conf    = 'new vhost.conf data';
        $form->project_files = [
            'type'  => 'local',
            'local' => [ 'source' => '/path/to/glory' ]
        ];

        $updatedService = $this->worker->update($service, $form);

        $updatedBuild = $updatedService->getBuild();

        $this->assertEquals($form->system_packages, $updatedBuild->getArgs()['SYSTEM_PACKAGES']);
        $this->assertEquals($form->enabled_modules, $updatedBuild->getArgs()['APACHE_MODULES_ENABLE']);
        $this->assertEquals($form->disabled_modules, $updatedBuild->getArgs()['APACHE_MODULES_DISABLE']);

        $this->assertEquals(
            'Host:updatedServerName,aliasA,aliasB',
            $updatedService->getLabels()['traefik.frontend.rule']
        );

        $fileDockerFile = $updatedService->getVolume('Dockerfile');
        $fileApache2    = $updatedService->getVolume('apache2.conf');
        $filePorts      = $updatedService->getVolume('ports.conf');
        $fileVhost      = $updatedService->getVolume('vhost.conf');

        $this->assertEquals($form->system_file['Dockerfile'], $fileDockerFile->getData());
        $this->assertEquals($form->system_file['apache2.conf'], $fileApache2->getData());
        $this->assertEquals($form->system_file['ports.conf'], $filePorts->getData());
        $this->assertEquals($form->vhost_conf, $fileVhost->getData());
    }
}
