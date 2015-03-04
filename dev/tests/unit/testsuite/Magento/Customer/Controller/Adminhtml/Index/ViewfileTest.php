<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Customer\Controller\Adminhtml\Index;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewfileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRawFactoryMock;

    /**
     * @var \Magento\Framework\Controller\Result\Raw|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRawMock;

    /**
     * @var \Magento\Framework\Url\DecoderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlDecoderMock;

    /**
     * @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Core\Helper\File\Storage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var \Magento\Framework\Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileSystemMock;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $directoryMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    public function setUp()
    {
        $this->requestMock = $this->getMock('Magento\Framework\App\RequestInterface', [], [], '', false);
        $this->responseMock = $this->getMock('Magento\Framework\App\ResponseInterface', [], [], '', false);
        $this->directoryMock = $this->getMock(
            'Magento\Framework\Filesystem\Directory\ReadInterface',
            [],
            [],
            '',
            false
        );
        $this->fileSystemMock = $this->getMock('Magento\Framework\Filesystem', [], [], '', false);
        $this->storage = $this->getMock('Magento\Core\Helper\File\Storage', [], [], '', false);
        $this->objectManager = $this->getMock('Magento\Framework\ObjectManagerInterface', [], [], '', false);

        $this->contextMock = $this->getMock('Magento\Backend\App\Action\Context', [], [], '', false);
        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock->expects($this->any())->method('getObjectManager')->willReturn($this->objectManager);

        $this->urlDecoderMock = $this->getMock('Magento\Framework\Url\DecoderInterface', [], [], '', false);
        $this->resultRawMock = $this->getMock('Magento\Framework\Controller\Result\Raw', [], [], '', false);

        $this->resultRawFactoryMock = $this->getMock(
            'Magento\Framework\Controller\Result\RawFactory',
            ['create'],
            [],
            '',
            false
        );
    }

    /**
     * @throws \Magento\Framework\App\Action\NotFoundException
     * @expectedException \Magento\Framework\App\Action\NotFoundException
     */
    public function testExecuteNoParamsShouldThrowException()
    {
        /** @var \Magento\Customer\Controller\Adminhtml\Index\Viewfile $controller */
        $controller = (new \Magento\TestFramework\Helper\ObjectManager($this))
            ->getObject('Magento\Customer\Controller\Adminhtml\Index\Viewfile');
        $controller->execute();
    }

    public function testExecuteParamFile()
    {
        $decodedFile = 'decoded_file';
        $file = 'file';
        $fileName = 'customer/' . $file;
        $path = 'path';

        $this->requestMock->expects($this->atLeastOnce())->method('getParam')->with('file')->willReturn($decodedFile);

        $this->directoryMock->expects($this->once())->method('getAbsolutePath')->with($fileName)->willReturn($path);

        $this->fileSystemMock->expects($this->once())->method('getDirectoryRead')
            ->with(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->willReturn($this->directoryMock);

        $this->storage->expects($this->once())->method('processStorageFile')->with($path)->willReturn(true);

        $this->objectManager->expects($this->any())->method('get')
            ->willReturnMap(
                [
                    ['Magento\Framework\Filesystem', $this->fileSystemMock],
                    ['Magento\Core\Helper\File\Storage', $this->storage]
                ]
            );

        $this->urlDecoderMock->expects($this->once())->method('decode')->with($decodedFile)->willReturn($file);

        $fileResponse = $this->getMock('Magento\Framework\App\ResponseInterface', [], [], '', false);
        $fileFactoryMock = $this->getMock('Magento\Framework\App\Response\Http\FileFactory', [], [], '', false);
        $fileFactoryMock->expects($this->once())->method('create')->with(
            $path,
            ['type' => 'filename', 'value' => $fileName],
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        )->willReturn($fileResponse);

        /** @var \Magento\Customer\Controller\Adminhtml\Index\Viewfile $controller */
        $controller = (new \Magento\TestFramework\Helper\ObjectManager($this))->getObject(
            'Magento\Customer\Controller\Adminhtml\Index\Viewfile',
            [
                'context' => $this->contextMock,
                'urlDecoder' => $this->urlDecoderMock,
                'fileFactory' => $fileFactoryMock
            ]
        );
        $controller->execute();
    }

    public function testExecuteGetParamImage()
    {
        $decodedFile = 'decoded_file';
        $file = 'file';
        $fileName = 'customer/' . $file;
        $path = 'path';
        $stat = ['size' => 10, 'mtime' => 10];

        $this->requestMock->expects($this->any())->method('getParam')
            ->willReturnMap([['file', null, null], ['image', null, $decodedFile]]);

        $this->directoryMock->expects($this->once())->method('getAbsolutePath')->with($fileName)->willReturn($path);
        $this->directoryMock->expects($this->once())->method('stat')->with($path)->willReturn($stat);

        $this->fileSystemMock->expects($this->once())->method('getDirectoryRead')
            ->with(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->willReturn($this->directoryMock);

        $this->storage->expects($this->once())->method('processStorageFile')->with($path)->willReturn(true);

        $this->objectManager->expects($this->any())->method('get')
            ->willReturnMap(
                [
                    ['Magento\Framework\Filesystem', $this->fileSystemMock],
                    ['Magento\Core\Helper\File\Storage', $this->storage]
                ]
            );


        $this->urlDecoderMock->expects($this->once())->method('decode')->with($decodedFile)->willReturn($file);

        $this->resultRawMock->expects($this->once())->method('setHttpResponseCode')->with(200)->willReturnSelf();
        $this->resultRawMock->expects($this->any())->method('setHeader')
            ->willReturnMap(
                [
                    ['Pragma', 'public', true, $this->resultRawMock],
                    ['Content-type', 'application/octet-stream', true, $this->resultRawMock],
                    ['Content-Length', $stat['size'], false, $this->resultRawMock],
                    ['Pragma', 'public', true, $this->resultRawMock],
                ]
            );

        $this->resultRawFactoryMock = $this->getMock(
            'Magento\Framework\Controller\Result\RawFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->resultRawFactoryMock->expects($this->once())->method('create')->willReturn($this->resultRawMock);

        /** @var \Magento\Customer\Controller\Adminhtml\Index\Viewfile $controller */
        $controller = (new \Magento\TestFramework\Helper\ObjectManager($this))->getObject(
            'Magento\Customer\Controller\Adminhtml\Index\Viewfile',
            [
                'context' => $this->contextMock,
                'urlDecoder' => $this->urlDecoderMock,
                'resultRawFactory' => $this->resultRawFactoryMock
            ]
        );
        $this->assertSame($this->resultRawMock, $controller->execute());
    }
}