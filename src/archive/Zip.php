<?php
use nicomollet\backup\archive\Archive;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use \ZipArchive;

/**
 * Component for packing and extracting files and directories using zip compression.
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
class Zip extends Archive
{

    /**
     * @inheritdoc
     * @throws InvalidConfigException if extension "zip"  is not enabled
     */
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

    /**
     * @inheritdoc
     */
    public function addFileToBackup($name, $file)
    {
        $relativePath = $name . DIRECTORY_SEPARATOR;
        $relativePath .= pathinfo($file, PATHINFO_BASENAME);
        $zipFile = new ZipArchive();
        $zipFile->open($this->backup, ZipArchive::CREATE);
        $zipFile->addFile($file, $relativePath);
        return $zipFile->close();
    }

    /**
     * @inheritdoc
     */
    public function addFolderToBackup($name, $folder)
    {
        $zipFile = new ZipArchive();
        $zipFile->open($this->backup, ZipArchive::CREATE);
        $directory = is_array($folder) ? Yii::getAlias($folder['path']) : Yii::getAlias($folder);
        $regex = is_array($folder) ? $folder['regex'] : null;
        $files = $this->getDirectoryFiles($directory, $regex);

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $name . DIRECTORY_SEPARATOR . substr($filePath, strlen($directory) + 1);
            $zipFile->addFile($filePath, $relativePath);
        }
        return $zipFile->close();
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function extractFolderFromBackup($name, $folder)
    {
        $zipFile = new ZipArchive();
        $zipFile->open($this->backup);
        $directory = is_array($folder) ? Yii::getAlias($folder['path']) : Yii::getAlias($folder);
        $targetPath = $directory . DIRECTORY_SEPARATOR;
        for ($i = 0; $i < $zipFile->numFiles; $i++) {
            $entryName = $zipFile->getNameIndex($i);
            $pos = strpos($entryName, "{$name}/");
            if ($pos === false || $pos != 0) {
                continue;
            }

            $file = $targetPath . substr($entryName, strlen($name) + 1);
            $dir = dirname($file);
            if (!is_dir($dir)) {
                FileHelper::createDirectory($dir, 0777, true);
            }

            $fpr = $zipFile->getStream($entryName);
            $fpw = fopen($file, 'w');

            while ($data = stream_get_contents($fpr, 1024)) {
                fwrite($fpw, $data);
            }

            fclose($fpr);
            fclose($fpw);
        }
        $zipFile->close();
    }

}
