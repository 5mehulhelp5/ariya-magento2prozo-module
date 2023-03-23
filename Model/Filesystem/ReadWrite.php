<?php

/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Model\Filesystem;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Class ReadWrite for get information
 */
class ReadWrite
{

    const PROZO_FILE_NAME = 'prozotoken.txt';
    
    /**
     * Prozo Helper 
     */
    protected $_prozoIntHelper;

    /**
     * @var DirectoryList
     */
    
    private $directoryList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    private $file;

    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        File $file,
        DirectoryList $directoryList,
        Filesystem $filesystem
    ){
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->file = $file;
    }

    /**
     * create custom folder and write text file
     *
     * @return bool
     */
    public function createAuthTokenFile($fileData){
        $varDirectory = $this->filesystem->getDirectoryWrite(
            DirectoryList::VAR_DIR
        );
        $varPath = $this->directoryList->getPath('var');
        $fileName = self::PROZO_FILE_NAME;
        $path = $varPath . '/prozo/' . $fileName;
        // Write Content
        $this->write($varDirectory, $path, $fileData);
    }

    /**
     * Write content to text file
     *
     * @param WriteInterface $writeDirectory
     * @param $filePath
     * @return bool
     * @throws FileSystemException
     */
    public function write(WriteInterface $writeDirectory, string $filePath, $fileData){
        $stream = $writeDirectory->openFile($filePath, 'w+');
        $stream->lock();
        $stream->write($fileData);
        $stream->unlock();
        $stream->close();
        return true;
    }

    public function getTokenReadFile(){
        try {
            $varDirectory = $this->filesystem->getDirectoryWrite(
                DirectoryList::VAR_DIR
            );
            $varPath = $this->directoryList->getPath('var');
            $fileName = self::PROZO_FILE_NAME;
            $path = $varPath . '/prozo/' . $fileName;
            return $this->file->fileGetContents($path);
        }catch(FileSystemException $e) {
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return false;
        }
    }
}