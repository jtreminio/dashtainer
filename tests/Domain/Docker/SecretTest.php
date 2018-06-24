<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Secret;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Tests\Mock;

use Doctrine\ORM;
use Doctrine\Common\Collections;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SecretTest extends KernelTestCase
{
    /** @var Secret */
    protected $secret;

    protected function setUp()
    {
        /** @var $em MockObject|ORM\EntityManagerInterface */
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->secret = new Secret(new Mock\RepoDockerSecret($em));
    }

    protected function createProjectSecret(string $name) : Entity\Secret
    {
        $secret = new Entity\Secret();
        $secret->fromArray(['id' => $name]);
        $secret->setName($secret->getId());

        return $secret;
    }

    protected function createServiceSecrete(string $name) : Entity\ServiceSecret
    {
        $secret = new Entity\ServiceSecret();
        $secret->fromArray(['id' => $name]);
        $secret->setName($secret->getId());

        return $secret;
    }

    protected function createService(string $name) : Entity\Service
    {
        $service = new Entity\Service();
        $service->fromArray(['id' => $name]);
        $service->setName($service->getId());

        return $service;
    }

    public function testDeleteAllForService()
    {
        $projectSecretA = $this->createProjectSecret('project-secret-a');
        $projectSecretB = $this->createProjectSecret('project-secret-b');
        $projectSecretC = $this->createProjectSecret('project-secret-c');

        $serviceSecretA = $this->createServiceSecrete('service-secret-a');
        $serviceSecretB = $this->createServiceSecrete('service-secret-b');
        $serviceSecretC = $this->createServiceSecrete('service-secret-c');
        $serviceSecretD = $this->createServiceSecrete('service-secret-d');
        $serviceSecretE = $this->createServiceSecrete('service-secret-e');
        $serviceSecretF = $this->createServiceSecrete('service-secret-f');
        $serviceSecretG = $this->createServiceSecrete('service-secret-g');

        $serviceA = $this->createService('service-a');
        $serviceB = $this->createService('service-b');

        $project = new Entity\Project();
        $project->addService($serviceA)
            ->addService($serviceB)
            ->addSecret($projectSecretA)
            ->addSecret($projectSecretB)
            ->addSecret($projectSecretC);

        /*
         * Owned, granted to other Services
         *
         * projectSecretA -> serviceA
         *      serviceSecretA
         *          serviceA
         *      serviceSecretB
         *          serviceB
         */
        $serviceA->addSecret($serviceSecretA);
        $serviceB->addSecret($serviceSecretB);
        $projectSecretA->addServiceSecret($serviceSecretA)
            ->addServiceSecret($serviceSecretB)
            ->setOwner($serviceA);

        /*
         * Owned, not granted to other Services
         *
         * projectSecretB -> serviceA
         *      serviceSecretC
         *          serviceA
         */
        $serviceA->addSecret($serviceSecretC);
        $projectSecretB->addServiceSecret($serviceSecretC)
            ->setOwner($serviceA);

        /*
         * Not owned, granted
         *
         * projectSecretC -> serviceB
         *      serviceSecretD
         *          serviceB
         *      serviceSecretE
         *          serviceA
         */
        $serviceB->addSecret($serviceSecretD);
        $serviceA->addSecret($serviceSecretE);
        $projectSecretC->addServiceSecret($serviceSecretD)
            ->addServiceSecret($serviceSecretE)
            ->setOwner($serviceB);

        /*
         * No ProjectSecret, only ServiceSecret
         *
         * serviceSecretF
         *      serviceA
         */
        $serviceA->addSecret($serviceSecretF);

        /*
         * Not owned by or granted to Service
         *
         * serviceSecretG
         *      serviceB
         */
        $serviceB->addSecret($serviceSecretG);

        $this->secret->deleteAllForService($serviceA);

        $this->assertEmpty($serviceA->getSecrets());
        $this->assertFalse($serviceB->getSecrets()->contains($serviceSecretB));

        $this->assertNull($serviceSecretA->getService());
        $this->assertNull($serviceSecretA->getProjectSecret());
        $this->assertNull($serviceSecretB->getService());
        $this->assertNull($serviceSecretB->getProjectSecret());

        $this->assertNull($serviceSecretC->getService());
        $this->assertNull($serviceSecretC->getProjectSecret());

        $this->assertNull($serviceSecretE->getService());
        $this->assertNull($serviceSecretE->getProjectSecret());
        $this->assertFalse($projectSecretC->getServiceSecrets()->contains($serviceSecretE));

        $this->assertNull($serviceSecretF->getService());

        $this->assertSame($serviceB, $serviceSecretD->getService());
        $this->assertSame($serviceB, $serviceSecretG->getService());
        $this->assertSame($serviceB, $projectSecretC->getOwner());
        $this->assertSame($projectSecretC, $serviceSecretD->getProjectSecret());
    }

    public function testGetForNewServiceReturnsSecrets()
    {
        $grantableProjectSecretA    = $this->createProjectSecret('project-secret-a');
        $notGrantableProjectSecretB = $this->createProjectSecret('project-secret-b');

        $grantableServiceSecretA    = $this->createServiceSecrete('service-secret-a');
        $notGrantableServiceSecretB = $this->createServiceSecrete('service-secret-b');

        $serviceA = $this->createService('service-a');

        $project = new Entity\Project();
        $project->addService($serviceA)
            ->addSecret($grantableProjectSecretA);

        // Grantable: ServiceSecret with ProjectSecret, owned by Service
        $serviceA->addSecret($grantableServiceSecretA);
        $grantableProjectSecretA->addServiceSecret($grantableServiceSecretA)
            ->setOwner($serviceA);

        // Not Grantable: ServiceSecret with ProjectSecret, not owned by Service
        $serviceA->addSecret($notGrantableServiceSecretB);
        $notGrantableProjectSecretB->addServiceSecret($notGrantableServiceSecretB);

        $serviceType = new Entity\ServiceType();
        $serviceType->fromArray(['id' => 'service-type']);

        $metaSecretA = new Entity\ServiceTypeMeta();
        $metaSecretA->fromArray(['id' => 'internal_secret_a']);
        $metaSecretA->setName($metaSecretA->getId())
            ->setData([
                'name' => 'internal_secret_a',
                'data' => 'internal_secret_a data',
            ]);

        $metaSecretB = new Entity\ServiceTypeMeta();
        $metaSecretB->fromArray(['id' => 'internal_secret_b']);
        $metaSecretB->setName($metaSecretB->getId())
            ->setData([
                'name' => 'internal_secret_b',
                'data' => 'internal_secret_b data',
            ]);

        $serviceType->addMeta($metaSecretA)
            ->addMeta($metaSecretB);

        $internalSecretsArray = [
            'internal_secret_a',
            'internal_secret_b',
        ];

        $result = $this->secret->getForNewService($project, $serviceType, $internalSecretsArray);

        $owned     = $result['owned'];
        $granted   = $result['granted'];
        $grantable = $result['grantable'];

        /** @var Entity\ServiceSecret $internalServiceSecretA */
        $internalServiceSecretA = $owned->get('internal_secret_a');
        $internalProjectSecretA = $internalServiceSecretA->getProjectSecret();

        /** @var Entity\ServiceSecret $internalServiceSecretB */
        $internalServiceSecretB = $owned->get('internal_secret_b');
        $internalProjectSecretB = $internalServiceSecretB->getProjectSecret();

        $this->assertEquals('internal_secret_a', $internalServiceSecretA->getName());
        $this->assertEquals('internal_secret_a data', $internalProjectSecretA->getData());

        $this->assertEquals('internal_secret_b', $internalServiceSecretB->getName());
        $this->assertEquals('internal_secret_b data', $internalProjectSecretB->getData());

        $this->assertEmpty($granted->count());

        $this->assertTrue($grantable->containsKey($grantableProjectSecretA->getName()));
        $this->assertSame($grantableServiceSecretA, $grantable->get($grantableProjectSecretA->getName()));
    }

    public function testGetForExistingService()
    {
        $grantedProjectSecretA   = $this->createProjectSecret('project-secret-a');
        $grantableProjectSecretB = $this->createProjectSecret('project-secret-b');

        $grantedServiceSecretA   = $this->createServiceSecrete('service-secret-a');
        $grantableServiceSecretB = $this->createServiceSecrete('service-secret-b');

        $serviceA = $this->createService('service-a');

        $project = new Entity\Project();
        $project->addService($serviceA)
            ->addSecret($grantedProjectSecretA)
            ->addSecret($grantableProjectSecretB);

        // Grantable, to be Granted: ServiceSecret with ProjectSecret, owned by Service
        $serviceA->addSecret($grantedServiceSecretA);
        $grantedServiceSecretA->setIsInternal(true);
        $grantedProjectSecretA->addServiceSecret($grantedServiceSecretA)
            ->setOwner($serviceA)
            ->setData('project secret a data');

        // Grantable, not Granted: ServiceSecret with ProjectSecret, owned by Service
        $serviceA->addSecret($grantableServiceSecretB);
        $grantableServiceSecretB->setIsInternal(true);
        $grantableProjectSecretB->addServiceSecret($grantableServiceSecretB)
            ->setOwner($serviceA)
            ->setData('project secret b data');

        $serviceType = new Entity\ServiceType();
        $serviceType->fromArray(['id' => 'service-type']);

        $metaSecretA = new Entity\ServiceTypeMeta();
        $metaSecretA->fromArray(['id' => 'internal_secret_a']);
        $metaSecretA->setName($metaSecretA->getId())
            ->setData([
                'name' => 'internal_secret_a',
                'data' => 'internal_secret_a data',
            ]);

        $metaSecretB = new Entity\ServiceTypeMeta();
        $metaSecretB->fromArray(['id' => 'internal_secret_b']);
        $metaSecretB->setName($metaSecretB->getId())
            ->setData([
                'name' => 'internal_secret_b',
                'data' => 'internal_secret_b data',
            ]);

        $serviceType->addMeta($metaSecretA)
            ->addMeta($metaSecretB);

        ///////

        $internalProjectSecretA = $this->createProjectSecret('internal_secret_a-project_secret');
        $ownedProjectSecretA    = $this->createProjectSecret('owned_secret');

        $internalServiceSecretA  = $this->createServiceSecrete('internal_secret_a');
        $grantedServiceSecretA_A = $this->createServiceSecrete('service-secret-a-granted');
        $ownedServiceSecret      = $this->createServiceSecrete('owned_secret');

        $currentService = $this->createService('current-service');
        $project->addService($currentService);

        // Owned, grantable
        $currentService->addSecret($internalServiceSecretA);
        $internalServiceSecretA->setIsInternal(true);
        $internalProjectSecretA->addServiceSecret($internalServiceSecretA)
            ->setOwner($currentService)
            ->setData('user defined data for internal secret');

        $currentService->addSecret($ownedServiceSecret);
        $ownedProjectSecretA->addServiceSecret($ownedServiceSecret)
            ->setOwner($currentService)
            ->setData('user defined data for owned secret');

        // Not owned, granted
        $currentService->addSecret($grantedServiceSecretA_A);
        $grantedProjectSecretA->addServiceSecret($grantedServiceSecretA_A);

        $internalSecretsArray = [
            'internal_secret_a',
            'internal_secret_b',
        ];

        $result = $this->secret->getForExistingService($currentService, $serviceType, $internalSecretsArray);

        $owned     = $result['owned'];
        $granted   = $result['granted'];
        $grantable = $result['grantable'];

        $this->assertEquals(3, $owned->count());
        /** @var Entity\ServiceSecret $resultOwnedServiceSecretA */
        $resultOwnedServiceSecretA = $owned->get('internal_secret_a');
        /** @var Entity\ServiceSecret $resultOwnedServiceSecretB */
        $resultOwnedServiceSecretB = $owned->get('internal_secret_b');
        /** @var Entity\ServiceSecret $resultOwnedServiceSecretC */
        $resultOwnedServiceSecretC = $owned->get('owned_secret');

        $this->assertSame($internalServiceSecretA, $resultOwnedServiceSecretA);
        $this->assertSame($internalProjectSecretA, $resultOwnedServiceSecretA->getProjectSecret());

        $resultOwnedProjectSecretB = $resultOwnedServiceSecretB->getProjectSecret();

        $this->assertEquals('internal_secret_b', $resultOwnedServiceSecretB->getName());
        $this->assertEquals('internal_secret_b data', $resultOwnedProjectSecretB->getData());

        $this->assertSame($ownedServiceSecret, $resultOwnedServiceSecretC);
        $this->assertSame($ownedProjectSecretA, $resultOwnedServiceSecretC->getProjectSecret());

        $this->assertEquals(1, $granted->count());
        /** @var Entity\ServiceSecret $resultGrantedServiceSecret */
        $resultGrantedServiceSecret = $granted->get('project-secret-a');
        $this->assertSame($grantedProjectSecretA, $resultGrantedServiceSecret->getProjectSecret());

        $this->assertEquals(1, $grantable->count());
        /** @var Entity\ServiceSecret $resultGrantableServiceSecret */
        $resultGrantableServiceSecret = $grantable->get('project-secret-b');
        $this->assertSame($grantableProjectSecretB, $resultGrantableServiceSecret->getProjectSecret());
    }
}
