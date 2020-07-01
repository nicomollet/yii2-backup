<?php

namespace amoracr\backup\archive;

use Yii;
use yii\base\InvalidConfigException;
use \ZipArchive;
use amoracr\backup\archive\Archive;

/**
 * Description of Zip
 *
 * @author alonso
 */
class Zip extends Archive
{

    public function init()
    {
        parent::init();
        $this->extension = '.zip';

        if (!empty($this->file)) {
            $this->backup = $this->file;
        } else {
            $this->backup = $this->path . $this->name . $this->extension;
        }

        if (!extension_loaded('zip')) {
            throw new InvalidConfigException('Extension "zip" must be enabled.');
        }
    }

    public function addFileToBackup($name, $file)
    {
        $relativePath = $name . DIRECTORY_SEPARATOR;
        $relativePath .= pathinfo($file, PATHINFO_BASENAME);
        $zipFile = new ZipArchive();
        $zipFile->open($this->backup, ZipArchive::CREATE);
        $zipFile->addFile($file, $relativePath);
        return $zipFile->close();
    }

    public function addFolderToBackup($name, $folder)
    {
        $zipFile = new ZipArchive();
        $zipFile->open($this->backup, ZipArchive::CREATE);
        $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(Yii::getAlias($folder)), \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            if (!$file->isDir() && !in_array($fileName, $this->skipFiles)) {
                $filePath = $file->getRealPath();
                $relativePath = $name . DIRECTORY_SEPARATOR . substr($filePath, strlen(Yii::getAlias($folder)) + 1);
                $zipFile->addFile($filePath, $relativePath);
            }
        }
        return $zipFile->close();
    }

    public function extractFileFromBackup($name, $file)
    {
        $zipFile = new ZipArchive();
        $zipFile->open($this->backup);
        $fpr = $zipFile->getStream($name);

        if (false !== $fpr) {
            $fpw = fopen($file, 'w');

            while ($data = stream_get_contents($fpr, 1024)) {
                fwrite($fpw, $data);
            }

            fclose($fpr);
            fclose($fpw);
        }

        $zipFile->close();
        return file_exists($file);
    }

}
