<?php
namespace nicomollet\backup\archive;

use nicomollet\backup\archive\Tar as TarArchive;
use Yii;
use yii\base\InvalidConfigException;
use \BadMethodCallException;
use \Exception;
use \Phar;
use \PharData;
use \UnexpectedValueException;

/**
 * Component for packing and extracting files and directories using Gzip compression.
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
class Gzip extends TarArchive
{

    /**
     * @inheritdoc
     * @throws InvalidConfigException if extension "zlib"  is not enabled
     */
    public function init()
    {
        parent::init();
        $this->extension = '.tar.gz';

        if (!empty($this->file)) {
            $this->backup = $this->file;
        } else {
            $this->backup = $this->path . $this->name . '.tar';
        }

        if (!Phar::canCompress(Phar::GZ)) {
            throw new InvalidConfigException('Extension "zlib" must be enabled.');
        }
    }

    /**
     * Closes backup file and tries to compress it
     *
     * @return boolean True if backup file was closed and compressed, false otherwise
     */
    public function close()
    {
        $flag = true;
        try {
            $archiveFile = new PharData($this->backup);
            $archiveFile->compress(Phar::GZ, $this->extension);
            $oldArchive = $this->backup;
            $this->backup = str_replace('.tar', $this->extension, $oldArchive);
            unset($archiveFile);
            Phar::unlinkArchive($oldArchive);
        } catch (UnexpectedValueException $ex) {
            Yii::error("Could not open '{$this->backup}'. Details: " . $ex->getMessage());
            $flag = false;
        } catch (BadMethodCallException $ex) {
            Yii::error("Technically, this should not happen. Details: " . $ex->getMessage());
            $flag = false;
        } catch (Exception $ex) {
            Yii::error("Unable to use backup file. Details: " . $ex->getMessage());
            $flag = false;
        }
        return $flag;
    }

}
