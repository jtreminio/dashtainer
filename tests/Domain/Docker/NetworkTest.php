<?php

namespace Dashtainer\Tests\Domain\Docker;

use Dashtainer\Domain\Docker\Network;
use Dashtainer\Entity\Docker as Entity;
use Dashtainer\Tests\Mock;

use Doctrine\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NetworkTest extends KernelTestCase
{
    /** @var Network */
    protected $network;

    protected function setUp()
    {
        /** @var $em MockObject|ORM\EntityManagerInterface */
        $em = $this->getMockBuilder(ORM\EntityManagerInterface::class)
            ->getMock();

        $this->network = new Network(new Mock\RepoDockerNetwork($em));
    }

    protected function createPrivateNetwork() : Entity\Network
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => 'private']);
        $network->setName('private')
            ->setIsPublic(false)
            ->setIsEditable(false);

        return $network;
    }

    protected function createPublicNetwork() : Entity\Network
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => 'public']);
        $network->setName('public')
            ->setIsPublic(true)
            ->setIsEditable(false);

        return $network;
    }

    protected function createNetwork(string $name) : Entity\Network
    {
        $network = new Entity\Network();
        $network->fromArray(['id' => $name]);
        $network->setName($name)
            ->setIsPublic(false)
            ->setIsEditable(true);

        return $network;
    }

    public function testGetForNewServiceReturnsNetworks()
    {
        $privateNetwork = $this->createPrivateNetwork();
        $publicNetwork  = $this->createPublicNetwork();
        $networkA       = $this->createNetwork('network-a');
        $networkB       = $this->createNetwork('network-b');
        $networkC       = $this->createNetwork('network-c');

        $project = new Entity\Project();
        $project->addNetwork($privateNetwork)
            ->addNetwork($publicNetwork)
            ->addNetwork($networkA)
            ->addNetwork($networkB)
            ->addNetwork($networkC);

        $internalVolumesArray = [
            'public',
        ];

        $result = $this->network->getForNewService($project, $internalVolumesArray);

        $joined   = $result['joined'];
        $unjoined = $result['unjoined'];

        $this->assertTrue($joined->contains($privateNetwork));
        $this->assertTrue($joined->contains($publicNetwork));

        $this->assertFalse($joined->contains($networkA));
        $this->assertFalse($joined->contains($networkB));
        $this->assertFalse($joined->contains($networkC));

        $this->assertTrue($unjoined->contains($networkA));
        $this->assertTrue($unjoined->contains($networkB));
        $this->assertTrue($unjoined->contains($networkC));

        $this->assertFalse($unjoined->contains($privateNetwork));
        $this->assertFalse($unjoined->contains($publicNetwork));
    }

    public function testGetForExistingServiceReturnsNetworks()
    {
        $privateNetwork = $this->createPrivateNetwork();
        $publicNetwork  = $this->createPublicNetwork();
        $networkA       = $this->createNetwork('network-a');
        $networkB       = $this->createNetwork('network-b');
        $networkC       = $this->createNetwork('network-c');

        $service = new Entity\Service();
        $service->addNetwork($privateNetwork)
            ->addNetwork($publicNetwork)
            ->addNetwork($networkA);

        $project = new Entity\Project();
        $project->addNetwork($privateNetwork)
            ->addNetwork($publicNetwork)
            ->addNetwork($networkA)
            ->addNetwork($networkB)
            ->addNetwork($networkC)
            ->addService($service);

        $internalVolumesArray = [
            'public',
        ];

        $result = $this->network->getForExistingService($service, $internalVolumesArray);

        $joined   = $result['joined'];
        $unjoined = $result['unjoined'];

        $this->assertTrue($joined->contains($privateNetwork));
        $this->assertTrue($joined->contains($publicNetwork));
        $this->assertTrue($joined->contains($networkA));

        $this->assertFalse($joined->contains($networkB));
        $this->assertFalse($joined->contains($networkC));

        $this->assertTrue($unjoined->contains($networkB));
        $this->assertTrue($unjoined->contains($networkC));

        $this->assertFalse($unjoined->contains($privateNetwork));
        $this->assertFalse($unjoined->contains($publicNetwork));
        $this->assertFalse($unjoined->contains($networkA));
    }

    public function testSaveAddsServiceToNetworksAndRemovesUnwanted()
    {
        $privateNetwork = $this->createPrivateNetwork();
        $publicNetwork  = $this->createPublicNetwork();
        $networkA       = $this->createNetwork('network-a');
        $networkB       = $this->createNetwork('network-b');
        $networkC       = $this->createNetwork('network-c');

        $service = new Entity\Service();
        $service->addNetwork($privateNetwork)
            ->addNetwork($publicNetwork)
            ->addNetwork($networkA);

        $project = new Entity\Project();
        $project->addNetwork($privateNetwork)
            ->addNetwork($publicNetwork)
            ->addNetwork($networkA)
            ->addNetwork($networkB)
            ->addNetwork($networkC)
            ->addService($service);

        $configs = [
            'private' => [
                'id'   => 'private',
                'name' => 'private',
            ],
            'public' => [
                'id'   => 'public',
                'name' => 'public',
            ],
            'new-network' => [
                'id'   => 'new-network',
                'name' => 'new-network',
            ],
        ];

        $this->network->save($service, $configs);

        $networks = $service->getNetworks();

        $this->assertNotNull($networks->remove($networks->indexOf($privateNetwork)));
        $this->assertNotNull($networks->remove($networks->indexOf($publicNetwork)));
        /** @var Entity\Network $newNetwork */
        $newNetwork = $networks->first();

        $this->assertEquals($newNetwork->getName(), 'new-network');

        $this->assertFalse($service->getNetworks()->contains($networkA));
        $this->assertFalse($service->getNetworks()->contains($networkB));
        $this->assertFalse($service->getNetworks()->contains($networkC));
    }
}
