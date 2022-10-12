<?php
namespace nicomollet\backup\db;

use nicomollet\backup\db\Database;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Component for dumping and restoring database data for MySql databases
 *
 * @author Alonso Mora <alonso.mora@gmail.com>
 * @since 1.0
 */
class Mysql extends Database
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->dumpCommand)) {
            $this->dumpCommand = 'mysqldump --add-drop-table --add-drop-trigger --allow-keywords --quick --complete-insert --add-locks --routines --triggers -u "{username}" -h "{host}" -p\'{password}\' {db} ';
        }
        if (empty($this->loadCommand)) {
            $this->loadCommand = 'mysql -u "{username}" -h "{host}" -p\'{password}\' {db} ';
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if configuration is not valid
     */
    public function dumpDatabase($dbHandle, $path)
    {
        $this->validateDumpCommand();
        $dumpCommand = $this->prepareCommand($dbHandle, $this->dumpCommand);
        $file = Yii::getAlias($path) . DIRECTORY_SEPARATOR . $dbHandle . '.sql';
        $command = sprintf("%s > %s 2> /dev/null", $dumpCommand, $file);
        system($command);

        if (!file_exists($file)) {
            return false;
        }

        return $file;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if configuration is not valid
     */
    public function importDatabase($dbHandle, $file)
    {
        $this->validateLoadCommand();
        $importCommand = $this->prepareCommand($dbHandle, $this->loadCommand);
        $command = sprintf("%s < %s 2> /dev/null", $importCommand, $file);
        system($command);

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function prepareCommand($dbHandle, $templateCommand)
    {
        $command = $templateCommand;
        $database = Yii::$app->$dbHandle->createCommand("SELECT DATABASE()")->queryScalar();
        $params = [
            'username' => Yii::$app->$dbHandle->username,
            'host' => 'localhost',
            'password' => Yii::$app->$dbHandle->password,
            'db' => $database,
        ];

        if ((string) $params['password'] === '') {
            $command = str_replace('-p\'{password}\'', '', $command);
            unset($params['password']);
        }

        return $this->replaceParams($command, $params);
    }

}
