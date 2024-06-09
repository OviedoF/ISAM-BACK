<?php

require('PDO.Log.class.php');
define('DB_SERVER_HOST', 'localhost'); 
define('DB_SERVER_PORT', '1433'); 
define('DB_CATALOG_NAME', 'isam_v5');
define('DB_USER_NAME', 'admin'); 
define('DB_PASSWORD', '123'); 

class DB
{
    private $Host;
    private $DBName;
    private $DBUser;
    private $DBPassword;
    private $DBPort;
    private $pdo;
    private $sQuery;
    private $bConnected = false;
    private $log;
    private $parameters;
    private $transactionCount = 0;
    public $lastMessageException;
    public $rowCount = 0;
    public $columnCount = 0;
    public $querycount = 0;
    public $success; // Definir la propiedad 'success'

    public function __construct()
    {
        $this->log = new Log();
        $this->Host = DB_SERVER_HOST;
        $this->DBName = DB_CATALOG_NAME;
        $this->DBUser = DB_USER_NAME;
        $this->DBPassword = DB_PASSWORD;
        $this->DBPort = DB_SERVER_PORT;
        $this->Connect();
        $this->parameters = array();
    }

    public function getNextSequence($sequenceType)
    {
        if (!$this->bConnected) return null;

        $seq = $this->getSingleValue("select sequenceId + 1 AS seq FROM ocd_sequence_id WHERE sequenceType = ?", $sequenceType);

        return ($seq == null ? 1 : $seq + 1);
    }

    public function isConnected()
    {
        return $this->bConnected;
    }

    public function getQueryObject()
    {
        return $this->sQuery;
    }

    private function Connect()
    {
        try {
            $this->pdo = new PDO('sqlsrv:server=' . $this->Host . ';database=' . $this->DBName, $this->DBUser, $this->DBPassword);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->bConnected = true;
        } catch (PDOException $e) {
            $this->ExceptionLog($e->getMessage());
            throw $e;
        }
    }

    public function BeginTransaction()
    {
        if ($this->pdo) {
            $this->pdo->beginTransaction();
            $this->transactionCount++;
        }
    }

    private function ResetTransactionCounter()
    {
        $this->transactionCount = 0;
    }

    public function RollBackTransaction()
    {
        if ($this->pdo && $this->transactionCount > 0) {
            $this->ResetTransactionCounter();
            $this->pdo->rollBack();
        }
    }

    public function CommitTransaction()
    {
        if ($this->pdo && $this->transactionCount > 0) {
            $this->ResetTransactionCounter();
            $this->pdo->commit();
        }
    }

    public function CloseConnection()
    {
        $this->pdo = null;
    }

    private function Init($query, $parameters = "")
    {
        if (!$this->bConnected) {
            $this->Connect();
        }
        try {
            $this->parameters = $parameters;
            $this->sQuery = $this->pdo->prepare($this->BuildParams($query, $this->parameters));
            if (!empty($this->parameters)) {
                if (array_key_exists(0, $parameters)) {
                    $parametersType = true;
                    array_unshift($this->parameters, "");
                    unset($this->parameters[0]);
                } else {
                    $parametersType = false;
                }
            }
            $this->success = $this->sQuery->execute();
            $this->querycount++;
        } catch (PDOException $e) {
            $this->ExceptionLog($e->getMessage(), $this->BuildParams($query));
            throw $e;
        }
        $this->parameters = array();
    }

    private function BuildParams($query, $params = null)
    {
        if (!empty($params)) {
            foreach ($params as $llave => $valor) {
                $valor = str_ireplace("'", '"', $valor);
                if (is_string($valor)) { $valor = "'" . $valor .  "'"; } 
                $query = str_ireplace(":" . $llave, $valor, $query);
            }
        }
        return $query;
    }

    private function queryResult($query, $params = null, $fetchmode = PDO::FETCH_OBJ, $className = null)
    {
        $query = trim($query);
        $rawStatement = explode(" ", $query);

        $this->Init($query, $params);
        $statement = trim(strtolower($rawStatement[0]));
        if ($statement === 'select' || $statement === 'show') 
        {            
            return $className === null ? $this->sQuery->fetchAll($fetchmode) : $this->sQuery->fetchAll($fetchmode, $className);
        } elseif ($statement === 'insert' || $statement === 'update' || $statement === 'delete') 
        {
            return $this->sQuery->rowCount();
        } else {
            return NULL;
        }
    }

    public function getByObject($query, $params = null)
    {
        return $this->queryResult($query, $params, PDO::FETCH_OBJ);
    }

    public function insertUpdateData($query, $params = null)
    {
        return $this->queryResult($query, $params, PDO::FETCH_OBJ);
    }

    public function getByClass($query, $className, $params = null)
    {
        return $this->queryResult($query, $params, PDO::FETCH_CLASS, $className);
    }

    public function getByName($query, $params = null)
    {
        return $this->queryResult($query, $params, PDO::FETCH_ASSOC);
    }

    public function getByArray($query, $params = null)
    {
        return $this->queryResult($query, $params, PDO::FETCH_NUM);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function getColumnSingle($query, $params = null)
    {
        $this->Init($query, $params);
        return $this->sQuery->fetchColumn();
    }

    public function getColumnAll($query, $params = null)
    {
        $this->Init($query, $params);
        $resultColumn = $this->sQuery->fetchAll(PDO::FETCH_COLUMN);
        $this->rowCount = $this->sQuery->rowCount();
        $this->columnCount = $this->sQuery->columnCount();
        $this->sQuery->closeCursor();
        return $resultColumn;
    }

    public function getSingleValue($query, $params = null)
    {
        $this->Init($query, $params);
        return $this->sQuery->fetch(PDO::FETCH_NUM)[0];
    }

    private function row($query, $params = null, $fetchmode = PDO::FETCH_OBJ, $className = null)
    {
        $this->Init($query, $params);
        if ($className === null)
        {                                    
            $resultRow = $this->sQuery->fetch($fetchmode);                        
        }
        else
        {
            $resultRow = $this->sQuery->fetch($fetchmode, $className);           
        }
        $this->rowCount = $this->sQuery->rowCount();
        $this->columnCount = $this->sQuery->columnCount();
        $this->sQuery->closeCursor();
        return $resultRow;
    }

    public function getRowByObject($query, $params = null)
    {
        return $this->row($query, $params, PDO::FETCH_OBJ);
    }

    public function getRowByArray($query, $params = null)
    {
        return $this->row($query, $params, PDO::FETCH_ASSOC);
    }

    public function getRowByClass($query, $className, $params = null)
    {
        return $this->row($query, $params, PDO::FETCH_CLASS, $className);
    }

    private function ExceptionLog($message, $sql = "")
    {
        $exception = 'Unhandled Exception. <br />';
        $exception .= $message;
        $exception .= "<br /> You can find the error back in the log.";

        if (!empty($sql)) {
            $message .= "\r\nRaw SQL : " . $sql;
        }
        $this->log->write($message, $this->DBName . md5($this->DBPassword));

        $this->lastMessageException = $exception;

        return $exception;
    }

}