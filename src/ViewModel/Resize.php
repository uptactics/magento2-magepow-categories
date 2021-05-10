<?php

declare(strict_types=1);

namespace HyvaThemes\Magento2MagepowCategories\ViewModel;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Resize implements ArgumentInterface
{
    /* @var Filesystem */
    private $_filesystem;

    /* @var StoreManagerInterface */
    private $_storeManager;

    /* @var AdapterFactory */
    private $_imageFactory;

    /* @var \Magento\Framework\Filesystem\Directory\WriteInterface */
    private $_directory;

    private $_logger;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        AdapterFactory $imageFactory,
        Filesystem $filesystem,
        LoggerInterface $logger
    )
    {
        $this->_storeManager = $storeManager;
        $this->_imageFactory = $imageFactory;
        $this->_filesystem = $filesystem;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_logger = $logger;
    }

    public function resize($srcImage, $w, $h = null)
    {
        try {
            if (empty($h)) $h = $w;
            if (is_string($srcImage)) {
                $store = $this->_storeManager->getStore();
                $mediaBaseUrl = $store->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                );
                $mediaDir = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
                $image = $mediaDir->getAbsolutePath('catalog/category/') . $srcImage;
                if ($this->_directory->isFile($image)) {
                    $targetDir = $mediaDir->getAbsolutePath('catalog/category/cache/' . $w . 'x' . $h);
                    if (!$this->_directory->isExist($targetDir)) {
                        $this->_directory->create($targetDir);
                    }
                    $destination = $targetDir . '/' . $srcImage;
                    $relativeDestination = $this->_directory->getRelativePath($destination);
                    if ($this->_directory->isFile($this->_directory->getRelativePath($destination))) {
                        return $mediaBaseUrl . $relativeDestination;
                    }
                    $resize = $this->_imageFactory->create();
                    $resize->open($image);
                    $resize->keepAspectRatio(true);
                    $resize->resize($w, $h);
                    $resize->save($destination);
                    if ($this->_directory->isFile($this->_directory->getRelativePath($destination))) {
                        return $mediaBaseUrl . $relativeDestination;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        return '';

    }
}
