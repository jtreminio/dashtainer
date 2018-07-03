<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker as Domain;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Tests\Mock;

class WorkerHandlerTest extends DomainAbstract
{
    /** @var Domain\WorkerHandler */
    protected $handler;

    /** @var Entity\ServiceType */
    protected $serviceTypeA;

    /** @var Entity\ServiceType */
    protected $serviceTypeB;

    /** @var Entity\ServiceType */
    protected $serviceTypeC;

    protected function setUp()
    {
        $workers = [
            new Mock\DomainDockerServiceWorkerA(),
            new Mock\DomainDockerServiceWorkerB(),
            new Mock\DomainDockerServiceWorkerC(),
        ];

        $this->serviceTypeA = $this->createServiceType(
            Mock\DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG
        );
        $this->serviceTypeB = $this->createServiceType(
            Mock\DomainDockerServiceWorkerB::SERVICE_TYPE_SLUG
        );
        $this->serviceTypeC = $this->createServiceType(
            Mock\DomainDockerServiceWorkerC::SERVICE_TYPE_SLUG
        );

        $repo = new Mock\RepoDockerServiceType($this->getEm());
        $repo->addServiceType($this->serviceTypeA);
        $repo->addServiceType($this->serviceTypeB);
        $repo->addServiceType($this->serviceTypeC);

        $bag     = new Domain\WorkerBag($workers, new Domain\ServiceType($repo));
        $network = new Domain\Network(new Mock\RepoDockerNetwork($this->getEm()));
        $repo    = new Mock\RepoDockerService($this->getEm());
        $secret  = new Domain\Secret(new Mock\RepoDockerSecret($this->getEm()));
        $service = new Domain\Service(new Mock\RepoDockerService($this->getEm()));
        $volume  = new Domain\Volume(new Mock\RepoDockerVolume($this->getEm()));

        $this->handler = new Domain\WorkerHandler(
            $bag, $network, $repo, $secret, $service, $volume
        );
    }

    public function testSetWorkerFromServiceTypeReturnsFalseOnServiceTypeNotFound()
    {
        $this->assertFalse(
            $this->handler->setWorkerFromServiceType('fake-service-type-slug')
        );
    }

    public function testSetServiceCalsSetWorkerFromServiceType()
    {
        $service = $this->createService('service')
            ->setType($this->serviceTypeA);

        $this->handler->setService($service);

        $this->assertTrue(
            is_a($this->handler->getWorker(), Mock\DomainDockerServiceWorkerA::class)
        );
    }

    public function testSetServiceSetsServiceTypeIfNotSet()
    {
        $service = $this->createService('service');

        $this->handler->setWorkerFromServiceType(
            Mock\DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG
        );

        $this->assertNull($service->getType());

        $this->handler->setService($service);

        $this->assertSame($this->serviceTypeA, $service->getType());
    }

    public function testSetServiceCreatesName()
    {
        $project = $this->createProject('project');

        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($project);

        $this->assertNull($service->getName());

        $this->handler->setService($service);

        $this->assertEquals('mock-worker-type-a-1', $service->getName());
    }

    public function testCreateAddsPorts()
    {
        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($this->createProject('project'))
            ->setVersion(1.2);

        $this->handler->setService($service);

        $form = $this->handler->getForm();
        $form->name = 'form-name';
        $form->ports = [
            [
                'published' => 123,
                'target'    => 321,
                'protocol'  => 'tcp',
            ],
            [
                'published' => 456,
                'target'    => 654,
                'protocol'  => 'udp',
            ],
        ];

        $this->handler->create();

        /** @var Entity\ServicePort $portA */
        $portA = $service->getPorts()->first();
        /** @var Entity\ServicePort $portB */
        $portB = $service->getPorts()->next();

        $this->assertEquals(123, $portA->getPublished());
        $this->assertEquals(321, $portA->getTarget());
        $this->assertEquals('tcp', $portA->getProtocol());

        $this->assertEquals(456, $portB->getPublished());
        $this->assertEquals(654, $portB->getTarget());
        $this->assertEquals('udp', $portB->getProtocol());
    }

    public function testUpdateRemovesOldAddsNewPorts()
    {
        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($this->createProject('project'))
            ->setVersion(1.2);

        $this->handler->setService($service);

        $form = $this->handler->getForm();
        $form->name = 'form-name';
        $form->ports = [
            [
                'published' => 123,
                'target'    => 321,
                'protocol'  => 'tcp',
            ],
            [
                'published' => 456,
                'target'    => 654,
                'protocol'  => 'udp',
            ],
        ];

        $this->handler->create();

        $form->ports = [
            [
                'published' => 999,
                'target'    => 111,
                'protocol'  => 'tcp',
            ],
        ];

        $this->handler->update();

        $this->assertCount(1, $service->getPorts());

        /** @var Entity\ServicePort $portA */
        $portA = $service->getPorts()->first();

        $this->assertEquals(999, $portA->getPublished());
        $this->assertEquals(111, $portA->getTarget());
        $this->assertEquals('tcp', $portA->getProtocol());
    }

    public function testGetCreateParams()
    {
        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($this->createProject('project'))
            ->setVersion(1.2);

        $this->handler->setService($service);

        $form = $this->handler->getForm();
        $form->name = 'form-name';

        $this->handler->create();

        $params = $this->handler->getCreateParams();

        $this->assertSame($form, $params['form']);
        $this->assertSame($service, $params['service']);
        $this->assertEquals('value1', $params['param1']);
    }

    public function testGetViewParams()
    {
        $form = Mock\DomainDockerServiceWorkerA::getFormInstance();
        $form->name = 'form-name';

        $project = $this->createProject('project');

        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setVersion(1.2);

        $this->handler->setWorkerFromServiceType(
            Mock\DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG
        );
        $this->handler->setForm($form);
        $this->handler->setService($service);

        $this->handler->create();
        $this->handler->update();

        $params = $this->handler->getViewParams();

        $this->assertSame($form, $params['form']);
        $this->assertSame($service, $params['service']);
        $this->assertEquals('value1', $params['param1']);
        $this->assertEquals('value2', $params['param2']);
    }

    public function testDeleteWithParent()
    {
        $form = Mock\DomainDockerServiceWorkerA::getFormInstance();
        $form->name = 'form-name';

        $project = $this->createProject('project');

        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setVersion(1.2);

        $parent = $this->createService('parent')
            ->setType($this->serviceTypeB)
            ->setProject($project)
            ->addChild($service);

        $this->createServiceMeta('meta-a')
            ->setService($service);
        $this->createServiceMeta('meta-a')
            ->setService($service);

        $this->handler->setWorkerFromServiceType(
            Mock\DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG
        );
        $this->handler->setForm($form);
        $this->handler->setService($service);
        $this->handler->delete();

        $this->assertNull($service->getParent());
        $this->assertFalse($parent->getChildren()->contains($service));
        $this->assertEmpty($service->getMetas());
    }

    public function testDeleteWithChildren()
    {
        $form = Mock\DomainDockerServiceWorkerA::getFormInstance();
        $form->name = 'form-name';

        $project = $this->createProject('project');

        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setVersion(1.2);

        $childA = $this->createService('child-a')
            ->setType($this->serviceTypeB)
            ->setProject($project)
            ->setParent($service);
        $childB = $this->createService('child-b')
            ->setType($this->serviceTypeC)
            ->setProject($project)
            ->setParent($service);

        $this->createServiceMeta('meta-a')
            ->setService($service);
        $this->createServiceMeta('meta-a')
            ->setService($service);

        $this->handler->setWorkerFromServiceType(
            Mock\DomainDockerServiceWorkerA::SERVICE_TYPE_SLUG
        );
        $this->handler->setForm($form);
        $this->handler->setService($service);
        $this->handler->delete();

        $this->assertNull($service->getParent());
        $this->assertFalse($service->getChildren()->contains($childA));
        $this->assertFalse($service->getChildren()->contains($childB));
        $this->assertEmpty($service->getMetas());
    }

    public function testManageChildrenCreate()
    {
        $form = new Mock\FormDockerServiceCreate();
        $form->name = 'form-name';
        $form->child_action = 'create';

        $project = $this->createProject('project');

        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setVersion(1.2);

        $this->handler->setWorkerFromServiceType(
            Mock\DomainDockerServiceWorkerC::SERVICE_TYPE_SLUG
        );
        $this->handler->setForm($form);
        $this->handler->setService($service);
        $this->handler->create();

        /** @var Entity\Service $child */
        $child = $service->getChildren()->first();

        $this->assertEquals('new-child', $child->getName());
    }

    public function testManageChildrenUpdate()
    {
        $form = new Mock\FormDockerServiceCreate();
        $form->name = 'form-name';
        $form->child_action = 'update';

        $project = $this->createProject('project');

        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setVersion(1.2);

        $child = $this->createService('child-service')
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setParent($service)
            ->setVersion(1.2);

        $this->handler->setWorkerFromServiceType(
            Mock\DomainDockerServiceWorkerC::SERVICE_TYPE_SLUG
        );
        $this->handler->setForm($form);
        $this->handler->setService($service);
        $this->handler->create();

        $this->assertEquals('updated-version', $child->getVersion());
    }

    public function testManageChildrenDelete()
    {
        $form = new Mock\FormDockerServiceCreate();
        $form->name = 'form-name';
        $form->child_action = 'delete';

        $project = $this->createProject('project');

        $service = (new Entity\Service())
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setVersion(1.2);

        $child = $this->createService('child-service')
            ->setType($this->serviceTypeA)
            ->setProject($project)
            ->setParent($service)
            ->setVersion(1.2);

        $this->handler->setWorkerFromServiceType(
            Mock\DomainDockerServiceWorkerC::SERVICE_TYPE_SLUG
        );
        $this->handler->setForm($form);
        $this->handler->setService($service);
        $this->handler->create();

        $this->assertFalse($service->getChildren()->contains(($child)));
        $this->assertNull($child->getParent());
    }
}
