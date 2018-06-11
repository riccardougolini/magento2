<?php
/**
 * Test class for \Magento\Bundle\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Bundle
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Bundle\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

class BundleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Bundle\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Bundle
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    /**
     * @var array
     */
    protected $bundleSelections;

    /**
     * @var array
     */
    protected $bundleOptionsRaw;

    /**
     * @var array
     */
    protected $bundleOptionsCleaned;

    protected function setUp()
    {
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $methods = [
            'getCompositeReadonly',
            'setBundleOptionsData',
            'setBundleSelectionsData',
            'getPriceType',
            'setCanSaveCustomOptions',
            'getProductOptions',
            'setOptions',
            'setCanSaveBundleSelections',
            '__wakeup',
            'getOptionsReadonly',
            'getBundleOptionsData',
            'getExtensionAttributes',
            'setExtensionAttributes',
        ];
        $this->productMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, $methods);
        $optionInterfaceFactory = $this->getMockBuilder(\Magento\Bundle\Api\Data\OptionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $linkInterfaceFactory = $this->getMockBuilder(\Magento\Bundle\Api\Data\LinkInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productRepository = $this->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customOptionFactory = $this->getMockBuilder(
            \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory::class
        )->disableOriginalConstructor()->getMock();
        $this->subjectMock = $this->createMock(
            \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper::class
        );
        $this->model = new \Magento\Bundle\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Bundle(
            $this->requestMock,
            $optionInterfaceFactory,
            $linkInterfaceFactory,
            $productRepository,
            $storeManager,
            $customOptionFactory
        );

        $this->bundleSelections = [
            ['postValue'],
        ];
        $this->bundleOptionsRaw = [
            'bundle_options' => [
                [
                    'title' => 'Test Option',
                    'bundle_selections' => $this->bundleSelections,
                ],
            ],
        ];
        $this->bundleOptionsCleaned = $this->bundleOptionsRaw['bundle_options'];
        unset($this->bundleOptionsCleaned[0]['bundle_selections']);
    }

    public function testAfterInitializeIfBundleAnsCustomOptionsAndBundleSelectionsExist()
    {
        $productOptionsBefore = [0 => ['key' => 'value'], 1 => ['is_delete' => false]];
        $valueMap = [
            ['bundle_options', null, $this->bundleOptionsRaw],
            ['affect_bundle_product_selections', null, 1],
        ];
        $this->requestMock->expects($this->any())->method('getPost')->will($this->returnValueMap($valueMap));
        $this->productMock->expects($this->any())->method('getCompositeReadonly')->will($this->returnValue(false));
        $this->productMock->expects($this->once())
            ->method('setBundleOptionsData')
            ->with($this->bundleOptionsCleaned);
        $this->productMock->expects($this->once())->method('setBundleSelectionsData')->with([$this->bundleSelections]);
        $this->productMock->expects($this->once())->method('getPriceType')->will($this->returnValue(0));
        $this->productMock->expects($this->any())->method('getOptionsReadonly')->will($this->returnValue(false));
        $this->productMock->expects($this->once())->method('setCanSaveCustomOptions')->with(true);
        $this->productMock->expects(
            $this->once()
        )->method(
            'getProductOptions'
        )->will(
            $this->returnValue($productOptionsBefore)
        );
        $this->productMock->expects($this->once())->method('setOptions')->with(null);
        $this->productMock->expects($this->once())->method('setCanSaveBundleSelections')->with(true);
        $this->productMock->expects($this->once())
            ->method('getBundleOptionsData')
            ->willReturn(['option_1' => ['delete' => 1]]);
        $extentionAttribute = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setBundleProductOptions'])
            ->getMockForAbstractClass();
        $extentionAttribute->expects($this->once())->method('setBundleProductOptions')->with([]);
        $this->productMock->expects($this->once())->method('getExtensionAttributes')->willReturn($extentionAttribute);
        $this->productMock->expects($this->once())->method('setExtensionAttributes')->with($extentionAttribute);
        $this->model->afterInitialize($this->subjectMock, $this->productMock);
    }

    public function testAfterInitializeIfBundleSelectionsDoNotExist()
    {
        $bundleOptionsRawWithoutSelections = $this->bundleOptionsRaw;
        $bundleOptionsRawWithoutSelections['bundle_options'][0]['bundle_selections'] = false;
        $valueMap = [
            ['bundle_options', null, $bundleOptionsRawWithoutSelections],
            ['affect_bundle_product_selections', null, false],
        ];
        $this->requestMock->expects($this->any())->method('getPost')->will($this->returnValueMap($valueMap));
        $this->productMock->expects($this->any())->method('getCompositeReadonly')->will($this->returnValue(false));
        $this->expectException(\Magento\Framework\Exception\CouldNotSaveException::class);
        $this->model->afterInitialize($this->subjectMock, $this->productMock);
    }
}
