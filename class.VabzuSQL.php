<?php
  
/**
 * VabzuSQL
 *  
 * Generic PHP class for handling MySQL Queries and Results 
 *  
 * This class will help you create MySQL Queries and show the results.  
 * Intead of passing in strings to the PHP Classes, I prefer to use assiciated 
 * arrays to make things more logical and easier to debug and maintain.
 *  
 * @package   VabzuSQL
 * @author    Aaron Jay Lev <aaronjaylev@gmail.com>
 * @copyright Copyright (c) 2013, 2020 Aaron Jay Lev
 * @link      http://www.aaronjay.com 
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Copyright 2019 Aaron Jay Lev
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.                                                                                                           
*/

class VabzuSQL {
	private $db_host = "localhost";
	private $db_name = "";
	private $db_user = "";
	private $db_pass = "";
	
	private $sql_link = 0;
	
	public function Connect($db_host = null, $db_name = null, $db_user = null, $db_pass = null) {
		if ($db_host !== null) $this->db_host = $db_host;
		if ($db_name !== null) $this->db_name = $db_name;
		if ($db_user !== null) $this->db_user = $db_user;
		if ($db_pass !== null) $this->db_pass = $db_pass;
		
		try {
			$this->sql_link = new PDO("mysql:host=" . $this->db_host . ';dbname=' . $this->db_name, $this->db_user, $this->db_pass);
			$this->sql_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
	 		die('Error: Problem with MySQL Database Connection - ' . $e->getMessage());
	 		
	 	}
		return 1;
	}
		
	private function MakeWhereQuery($whereArray) {
	// if whereArray is 0, return blank to return all rows
		if ($whereArray == 0) return '';
		
		if (! is_array($whereArray)) die('not an array in MakeWhereQuery');

		$st = '';
		foreach ($whereArray as $key => $value) {
         $st .= ($st == '' ? ' where ' : ' and ');
         if ($key == '') {
            $st .= $value;
         } else {
            $st .= $key . ' = :' . $key;
         }
  		}
			
		return $st;
	}

	private function MakeParams($whereArray) {
	// if whereArray is 0, return blank to return all rows
		if ($whereArray == 0) {
			return false;
		}
		
		if (! is_array($whereArray)) die('not an array in MakeParams');
		
		$ans = array();
		foreach ($whereArray as $key => $value) {
			if (strtolower($value) != "now()") {
				$ans[":" . $key] = $value;
			}
		}			
		return $ans;
	}
	
	private function MakeOrderQuery($orderArray) {
	
	// 9/1/11 - Updated to not force key/value combo
		if (! is_array($orderArray)) {
			if (!$orderArray || $orderArray == '') {
				return '';
			} else { // assume it's a single field string sort
				return " order by " . $orderArray;
			}
		} else {
			$st = '';
			foreach ($orderArray as $key => $value) {
				if ($st == '') {
					$st = ' order by ';
				} else {
					$st .= ', ';
				}
				$st .= ($key === 0 ? '' : $key) . ' ' . $value;
			}
			return $st;
		}
	}
	
	private function MakeFieldNames($fieldNames) {
		if ($fieldNames === 0) {
			return "*";
		} elseif (is_string($fieldNames)) {
			return $fieldNames;
		} elseif (is_array($fieldNames)) {
			return implode(', ', $fieldNames);
		} else {
			print_r($fieldNames);
			die('Unknown type in MakeFieldNames');
		}
  }

	public function DeleteRows($tableName, $whereArray) {
		if (! is_array($whereArray)) die('not an array in DeleteRows');

		$query = "delete from " . $tableName . $this->MakeWhereQuery($whereArray);

		$results = $this->sql_link->prepare($query);
 		$params = $this->MakeParams($whereArray);
 		 	 		
		try {
			if ($params === false) {
		 		$results->execute();
		 	} else {
		 		$results->execute($params);
		 	}
	 	} catch (PDOException $e) {
	 		die('Error: Problem with DeleteRows - ' . $e->getMessage());
	 	}
		return $results;
	} 
	
	public function SelectRows($tableName, $whereArray = 0, $fieldNames = 0, $orderArray = 0, $startpos=0, $norows=0) {
 		if (! is_array($whereArray) && $whereArray != 0) die('whereArray is not an array in SelectRows');
 		 		 		 
 		$query = "select " . $this->MakeFieldNames($fieldNames) . " from " . $tableName . $this->MakeWhereQuery($whereArray);
 		
 		if ($orderArray != 0) {
 			$query .= $this->MakeOrderQuery($orderArray);
 		}
 		
 		if (0 < $norows) {
 			$query .= " limit $startpos, $norows ";
		}	
				
		$results = $this->sql_link->prepare($query);
 		$params = $this->MakeParams($whereArray);
 		 	 		
		try {
			if ($params === false) {
		 		$results->execute();
		 	} else {
		 		$results->execute($params);
		 	}
	 	} catch (PDOException $e) {
	 		die('Error: Problem with SelectRows - ' . $e->getMessage());
	 	}
		return $results;
	} 

	public function SelectRow($tableName, $whereArray, $fieldNames = 0) {
		$results = $this->SelectRows($tableName, $whereArray, $fieldNames);
		
		if ($results->rowCount() == 0) { // not found
			return false;
		} else {
			$row = $results->fetch(PDO::FETCH_ASSOC);
			return $row;
		}
	}
	
	public function RunQuery($query, $params = false) {
		$results = $this->sql_link->prepare($query);

		try {
			if ($params === false) {
		 		$results->execute();
		 	} else {
		 		$results->execute($params);
		 	}
	 	} catch (PDOException $e) {
	 		die('Error: Problem with RunQuery - ' . $e->getMessage());
	 	}
		return $results;
	}
	

	public function getRowsAsArray($tableName, $key, $whereArray = 0, $fieldNames = 0) {
		$results = $this->SelectRows($tableName, $whereArray, $fieldNames);
		$a = array();
		while ($info = mysql_fetch_assoc($results)) {
			$a[$info[$key]] = $info;
		}
		return $a;
	}
   
   public function NextAutoNumber($table) {
      $query = 'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ' . 
         $this->db_name . ' AND TABLE_NAME = ' . $table;
      $results = $this->RunQuery($query);
      list($value) = mysql_fetch_row($results);
      return $value;
   }
      
   
	public function InsertRow($tableName, $valuesArray) {
		$query = "insert into " . $tableName . " (" . implode(", ", array_keys($valuesArray)) . ") ";
		$query .= "values (:" . implode(", :", array_keys($valuesArray)) . ")";			
		try {
			$results = $this->sql_link->prepare($query);
            $results->execute($valuesArray);
        } catch (Exception $e) {
			die('Error: Problem with InsertRow - ' . $e->getMessage());
		}
		return $this->sql_link->lastInsertId();
	}
	
	public function UpdateRows($tableName, $valuesArray, $whereArray) {
		$query = "update " . $tableName . " set ";
		$st = "";
		foreach ($valuesArray as $key => $value) {
			$useKey = (strtolower($value) == "now()" ? "now()" : ":" . $key);
			$st .= ($st == '' ? '' : ', ') . $key . ' = ' . $useKey;
		}
		$query .= $st . $this->MakeWhereQuery($whereArray);
			
		$results = $this->sql_link->prepare($query);
 		$params = $this->MakeParams(array_merge($valuesArray, $whereArray));

 		try {
	 		$results->execute($params);
	 	} catch (Exception $e) {
	 		die('Error: Problem with UpdateRows - ' . $e->getMessage());
	 	}
		return $results;
		
	}
	
	public function AutoInsertUpdate($tableName, $valuesArray, $whereArray) {
		$result = $this->SelectRows($tableName, $whereArray);
		if (mysql_num_rows($result) == 0) {
			return $this->InsertRow($tableName, array_merge($valuesArray, $whereArray));
		} else {
			return $this->UpdateRows($tableName, $valuesArray, $whereArray);
		}
	}	
	
	public function NextId($tableName) { 
		$results=$this->RunQuery("show table status like '".$tableName."' ");
		$info = mysql_fetch_array($results);
		return $info["Auto_increment"];
	}
	
	public function MakeValuesArray($keys, $arr) {
		$ans = array();
		foreach ($keys as $key) {
			if (array_key_exists($key, $arr)) {
				$ans[$key] = $arr[$key];
			} else {
				$ans[$key] = '';
			}
		}
		return $ans;
	}
    
    public function enum_select($table, $field) {
    	$query = "SHOW COLUMNS FROM $table LIKE '$field'";
    	$results = $this->RunQuery($query);
    	$row = mysql_fetch_array($results, MYSQL_NUM );
    	preg_match_all("/'(.*?)'/", $row[1], $enum_array );
    	return($enum_array[1]);
    } 
    
    
    public function getEnumValues($tableName, $FieldName) {
      $DBString = 'SHOW COLUMNS FROM ' . $tableName . " WHERE Field = '" . $FieldName . "'";
      $results = $this->RunQuery($DBString);
      $info = mysql_fetch_assoc($results);
      $st = $info['Type'];
      $st = substr($st, 5, strlen($st) - 6);
      $a = explode(",", $st);
      for ($i=0; $i < sizeof($a); $i++) {
         $a[$i] = substr($a[$i], 1, strlen($a[$i]) - 2);
      }
      return $a;
   }      
   
   public function makeOptions($tableName, $FieldName, $value = "") {
      $a = $this->getEnumValues($tableName, $FieldName);
      $st = '';
      foreach ($a as $option) {
         $st .= '<option' . ($option == $value ? ' selected' : '') . '>' . $option . '</option>' . "\n";
      }
      return $st;
   }
   
   	public function getDBTime() {
   		$results = $this->RunQuery('select now() as TheTime');
   		list($time) = mysql_fetch_row($results);
   		return strtotime($time);
   	}
   	
   	function RowCount($tableName, $whereArray=0){
		$results = $this->SelectRows($tableName, $whereArray);
		return $results->rowCount();	
	}

    
}

?>