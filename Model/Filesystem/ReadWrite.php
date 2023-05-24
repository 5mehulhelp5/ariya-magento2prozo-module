<?php
/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Model\Filesystem;

use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Class ReadWrite for get information
 */

class ReadWrite
{
    /**
     * Write content to text file
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
}