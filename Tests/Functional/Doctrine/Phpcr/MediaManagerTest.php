<?php

namespace Symfony\Cmf\Bundle\MediaBundle\Tests\Functional\Doctrine\Phpcr;

use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\Directory;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\Media;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\MediaManager;

class MediaManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $containerMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $registryMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dmMock;
    /**
     * @var Directory
     */
    private $testRoot;

    public function setUp()
    {
        $this->containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->registryMock = $this->getMockBuilder('Doctrine\Bundle\PHPCRBundle\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->dmMock = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->testRoot = new Directory();
        $this->testRoot->setId('/test/media');
    }

    private function getMediaManager()
    {
        return new MediaManager($this->registryMock, 'themanager', '/test/media');
    }

    public function testGetPath()
    {
        $media = new Media();
        $media->setId('/test/media/mymedia');
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('/test/media/mymedia', $mediaManager->getPath($media));
    }

    public function testGetUrlSafePath()
    {
        $media = new Media();
        $media->setId('/test/media/mymedia');
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('test/media/mymedia', $mediaManager->getUrlSafePath($media));
    }

    public function setDefaultsProvider()
    {
        return array(
            array('mymedia', 'mymedia'),
            array('mymedia', null, false, '/test/media/mymedia'),
            array(null, 'mymedia', true),
        );
    }

    /**
     * @dataProvider setDefaultsProvider
     */
    public function testSetDefaults($expectedName = null, $name = null, $nameExists = false, $id = null)
    {
        $returnMediaExists = $nameExists ? new Media() : null;

        $this->registryMock->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo('themanager'))
            ->will($this->returnValue($this->dmMock))
        ;
        $this->dmMock->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap(array(
                array('Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\Media', '/test/media/'.$name, $returnMediaExists),
                array(null, '/test/media', $this->testRoot),
            )))
        ;

        $media = new Media();
        $media->setId($id);
        $media->setName($name);

        $mediaManager = $this->getMediaManager();
        $mediaManager->setDefaults($media);

        $this->assertEquals($this->testRoot, $media->getParent());
        if ($expectedName) {
            $this->assertEquals($expectedName, $media->getName());
        } else {
            $this->assertNotEquals($name, $media->getName());
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetDefaultsException()
    {
        $media = new Media();

        $mediaManager = $this->getMediaManager();
        $mediaManager->setDefaults($media);
    }

    public function mapPathToIdProvider()
    {
        return array(
            array('/test/media/mymedia', null),
            array('/test/media/mymedia', '/test/media'),
        );
    }

    /**
     * @dataProvider mapPathToIdProvider
     */
    public function testMapPathToId($path, $rootPath)
    {
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('/test/media/mymedia', $mediaManager->mapPathToId($path, $rootPath));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testMapPathToIdException()
    {
        $mediaManager = $this->getMediaManager();

        $mediaManager->mapPathToId('/test/media/mymedia', '/out/of/bound');
    }

    public function mapUrlSafePathToIdProvider()
    {
        return array(
            array('test/media/mymedia', null),
            array('test/media/mymedia', '/test/media'),
        );
    }

    /**
     * @dataProvider mapUrlSafePathToIdProvider
     */
    public function testMapUrlSafePathToId($path, $rootPath)
    {
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('/test/media/mymedia', $mediaManager->mapPathToId($path, $rootPath));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testMapUrlSafePathToIdException()
    {
        $mediaManager = $this->getMediaManager();

        $mediaManager->mapUrlSafePathToId('test/media/mymedia', '/out/of/bound');
    }
}