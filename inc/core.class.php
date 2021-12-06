<?php


if (!file_exists("inc/config.php")) {
	trigger_error("The config file was not found, please make a copy of 'config.orig.php' and rename it to 'config.php' changing settings as needed.<br />", E_USER_ERROR);
}
else {
	require_once("inc/config.php");
}

set_include_path(Core::getConfVar('PEAR_DIR') . PATH_SEPARATOR . get_include_path());
require_once('MDB2.php');

class CourtState
{
	const NoOverride = -1; // Used in DB to define that we're not overriding the court status
	const Free = 0;
	const Taken = 1;
	const TakenByOverlap = 2;
	const ClosedForMaintenance = 3;
}

class Core
{
	private $errors = array();
	private $messages = array();
	
	// database specific variables:
	private $dsn = NULL, $options = NULL;
	private $mdb2 = NULL;

	public $verboseLog = NULL;
	private $debugging = TRUE;
	
	private $session_expire_limit = 30;
	
	function setDebugging($newValue) {
		$this->debugging = $newValue;
		if ($newValue) {
			ini_set('display_errors','On'); 
		}
		else {
			ini_set('display_errors','Off'); 
		}
	}
	
	function __construct()
	{
		$this->setDebugging(Config::$ENABLE_DEBUGGING);

		set_error_handler(array(&$this,"errorHandler"));
		date_default_timezone_set(Config::$PHP_TIMEZONE);

		// Why some hosts still use magic quotes is beyond me...
		if (get_magic_quotes_runtime())
			set_magic_quotes_runtime(false);

		$this->dsn = array(
			"phptype"       => "mysql", 
			"username"      => Config::$DB_USERNAME,
			"password"      => Config::$DB_PASSWORD,
			"hostspec"      => Config::$DB_HOSTNAME,
			"database"      => Config::$DB_DATABASE
		);

		$this->PASSWORD_SALT = Config::$PASSWORD_SALT;
	
		
		if (session_id() == "")
		{
			session_start();
		}
			
		$this->options = array(
			"fetch_class" => MDB2_FETCHMODE_ASSOC
		);
	}
	
	function getSessionExpireLimit()
	{
		return Config::$SESSION_EXPIRE_LIMIT;
	}

	function getAdminEmail()
	{
		return Config::$ADMIN_EMAIL;
	}

    function getSiteName()
    {
        return Config::$SITE_NAME;
    }

	function __destruct()
	{
		if ($this->mdb2 != NULL)
			$this->dbKillHandle($this->mdb2);
	}

	public static function getConfVar($var_name) {
		return Config::${$var_name};
	}

	public function errorHandler($errno, $errstr, $errfile, $errline) 
	{
		switch ($errno) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$type = "Notice";
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$type = "Warning";
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$type = "Fatal Error";
				break;
			default:
				$type = "Unknown";
				break;
		}

		if ($type != "Unknown")
		{
			$this->errors[] = $errstr.($this->debugging ? "on line " + $errline : ""); 

			if ($this->debugging)
			{
				$error = "<pre>";
				$trace = array_reverse(debug_backtrace()); 
				array_pop($trace);

				$n = 0;

				if (count($trace) == 0)
				{
					$error .= "-> $errfile($errline)";
				}

				foreach($trace as $item) 
				{
					for ($m = 0; $m < $n; $m++)
						$error .= "  ";
					$fields = split("/",$item['file']);
					$filename = $fields[count($fields)-1];
					$error .= "-> $filename({$item['line']}): ";
					$error .= (isset($item['class']) ? "{$item['class']}::" : "");
					$error .= "{$item['function']}";
					$args = array();
					if (isset($item['args']))
					{
						foreach ($item['args'] as $arg)
						{
							if (is_object($arg))
							{
								$args[] = get_class($arg).(method_exists(get_class($arg),"__toString") ? "[$arg]" : "");
							}

							else
								$args[] = "'$arg'";
						}
					}
					$error .= "(".join(", ",$args).")";
					$error .= "<br />";

					$n++;
				}

				$error .= "</pre>";

				$this->errors[] = $error;
			}
		}

		if (ini_get('log_errors'))
			error_log(sprintf("PHP %s:  %s in %s on line %d", $type, $errstr, $errfile, $errline));
		return true;
	}
		
	function debug($variable)
	{
		echo "<div><pre>";
		var_dump($variable);
		echo "</pre></div>";
	}

	function message($message)
	{
		$this->messages[] = $message;
	}

	function areMessages()
	{
		return (count($this->messages) > 0);
	}

	function outputMessages($join = "<br />")
	{
		return join($join, $this->messages);
	}
	
	function error($errorMessage)
	{
		$this->errors[] = $errorMessage;
	}
	
	function areErrors()
	{
		return (count($this->errors) > 0);
	}
	
	function outputErrors($join = "<br />")
	{
		return join($join, $this->errors);
	}

	function popMostRecentError()
	{
		return array_pop($this->errors);
	}
	
	// DATABASE FUNCTIONS:
	
	function dbQuery($dbHandle, $query, $errorMessage = NULL)
	{
		$this->verboseLog .= "<p>MYSQL QUERY<br />$query</p>";
		
		$res =& $dbHandle->query($query);
		if(PEAR::isError($res))
		{
			if ($this->debugging)
				$this->error($res->getMessage() . "<br /><br />" . "MYSQL QUERY: " . $query . "<br /><br />" . $errorMessage);
			else
				$this->error("$errorMessage (database problem)");
			return FALSE;
		}
		return $res;
	}
	
	function dbKillHandle($dbHandle)
	{
		//$this->debug(debug_backtrace());
		//echo $this->outputErrors();
		$dbHandle->disconnect();
	}	       
	
	function dbMakeHandle($errorMessage = NULL)
	{ 
		if (!isset($this))
		{       // only E_STRICT blocks static calls to non-static members, we block it ourselves
			die("Error: DB() should not be called directly!");
		}
		else if ($this->mdb2 != null)
		{
			return $this->mdb2;
		}
		else
		{
			$mdb2 =& MDB2::factory($this->dsn, $this->options);

			if (PEAR::isError($mdb2)) 
			{
							echo "<pre>";
							var_dump($mdb2);
							echo "</pre>";
			    die($mdb2->getMessage() . "\n\n" . $errorMessage);
							
							return FALSE;
			}
		       
			$mdb2->setFetchMode(MDB2_FETCHMODE_ASSOC);
			$mdb2->setOption('portability',MDB2_PORTABILITY_ALL ^ (MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_EMPTY_TO_NULL));

			$this->mdb2 = $mdb2;

			return $mdb2;
		}
		
	}
	
	function sendEmail($fromaddress,$fromname,$toaddress,$toname,$subject,$content,$attachment = '',$attachmentname='')
	{
		require_once("PHPMailer/class.phpmailer.php");
		$mail = new PHPMailer();
	
		$mail->IsSMTP();				   // send via SMTP
		$mail->Host     = Config::$SMTP_HOST; // SMTP servers
		$mail->SMTPAuth = false;     // turn on SMTP authentication
	
		$mail->From     = $fromaddress;
		$mail->FromName = $fromname;
		$mail->AddAddress("$toaddress",$toname); 
	
	
		$mail->Subject  =  $subject;
		$mail->Body     =  $content;
		//$mail->AltBody  =  "This is the text-only body";

		if(!$mail->Send())
		{	 
			$this->error("Message to not sent to $toaddress, mailer error: {$mail->ErrorInfo}");
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
    
}

class Toolkit
{
	function redirect($url)
	{
		echo "<script type='text/javascript'> window.location='$url'; </script>"; 
	}
	

    public function crsidIsTrinity($crsid)
    {
        $foundATrinitarian = FALSE;
        $info = Toolkit::ldapLookup($crsid);
        if ($info == "")
            return FALSE;
        $insts = $info[0]['instid'];
        
        array_shift($insts);
        foreach ($insts as $ldapAffiliatedInstitution)
        {
            if ($ldapAffiliatedInstitution == "TRINUG" || $ldapAffiliatedInstitution == "TRINPG" || $ldapAffiliatedInstitution == "TRIN")
            {
                $foundATrinitarian = TRUE;
                break;
            }
        }
        return $foundATrinitarian;
    }

    public function ldapLookup($crsid)
    {
        $info = NULL;
		if (!function_exists("ldap_connect"))
		{
			return FALSE;
		}
        $ds = ldap_connect("ldap://ldap.lookup.cam.ac.uk", 389);
        if ($ds)
        {
            $r = ldap_bind($ds);
            $result = ldap_search($ds, "ou=people,o=University of Cambridge,dc=cam,dc=ac,dc=uk", "uid=$crsid");
            $information = ldap_get_entries($ds, $result);

            if (ldap_count_entries($ds, $result) > 0)
            {
                $info = ldap_get_entries($ds, $result);
                ldap_close($ds);
            }
        }
        return $info;
    }

    public function nameFromLdapLookup($crsid)
    {
        $info = Toolkit::ldapLookup($crsid);
        if ($info != "")
        {
            return $info[0]['cn'][0];
        }
        return NULL;
    }
}


class T_DateTime
{
	public $value = NULL;
	
	public function __construct($setValue, $value = NULL)
	{
		if ($value != NULL)
			$this->value = $value;
		else
		{
			$this->value = strtotime($setValue);
			if (!$this->value)
			{
				trigger_error("'$setValue' is not a valid date and time (the format should be Y-m-d H:i:s)",E_USER_ERROR);
			}
		}
	}
	
	public function mysqlFormat()
	{
		return date('Y-m-d H:i:s', $this->value);
	}
	
	public function value()
	{
		return $this->value;
	}

	public function __toString()
	{
		return date("H:i Y-m-d",$this->value);
	}

	public function getTime()
	{
		return new T_time(date("H:i:s", $this->value));
	}

	public function getDate()
	{
		return date("jS M",$this->value);
	}
}

class T_Time
{
	private $value = NULL;
	private $zeroOffsetValue = NULL;
	
	public function __construct($setValue, $value = NULL)
	{
		if ($value != NULL)
			$this->value = $value;
		else
		{
			$this->value = strtotime("January 1 1970 $setValue");
			$this->zeroOffsetValue = strtotime("January 1 1970 $setValue UTC");
			if (!$this->value && $this->value !== 0)
			{
				trigger_error("$setValue is not a valid time (format is hh:mm:ss)", E_USER_ERROR);
			}
		}
	}
	
	public function mysqlFormat()
	{
		return date('H:i:s', $this->value);
	}
	
	public function value()
	{
		return $this->zeroOffsetValue;
	}
	
	public function __toString()
	{
		return date("H:i",$this->value);
	}

	public function getMinutes()
	{
		return date("H",$this->value)*60+date("i",$this->value);
	}
}

class T_String
{
	public $value = NULL;
	
	public function __construct(Core $core, $setValue)
	{
		$this->value = $setValue;
	}
	
	public function value()
	{
		return $this->value;
	}
	
	public function __toString()
	{
		return $this->value;
	}
}
/* Disable this for now, Trinity's server is pre PHP 5.3
class DatabaseItem
{
    protected $id = -1;
    protected static $object_name = NULL;

    protected static function tableName()
    {
        return static::$object_name . "s";
    }

    public static function get(Core $core, $searchString = NULL)
    {
		$dbHandle = $core->dbMakeHandle();
        $sql_string = sprintf("SELECT * FROM %s %s", static::tableName(), $searchString == NULL ? "" : " WHERE $searchString");
        echo $sql_string;
		$res =& $core->dbQuery($dbHandle, $sql_string);

        $records = array();
        if (isset($res) && $res != NULL && $res->numRows()) 
        {
            while ($data = $res->fetchRow()) 
            {
                $records[] = new static::$object_name($data);
            }
        }
        return $records;
    }

    protected function saveToDB(Core $core, $key_value_pairs)
    {
        $dbHandle = $core->dbMakeHandle();
        $res = NULL;
        
        if ($this->id != -1)
        {
            $sql_str_items = array();
            foreach ($key_value_pairs as $key => $value)
            {
                $sql_str_items[] = sprintf("%s=%s", $key, $dbHandle->quote($value));
            }

            $sql_string = sprintf("UPDATE %s SET %s WHERE id=%s", static::tableName(), join(", ", $sql_str_items), $this->id);
            $res =& $core->dbQuery($dbHandle, $sql_string);
        }
        else
        {
            $sql_fields = array();
            $sql_values = array();
            foreach ($key_value_pairs as $key => $value)
            {
                $sql_fields[] = $key;
                $sql_values[] = $dbHandle->quote($value);
            }

            $sql_string = sprintf("INSERT INTO %s(%s) VALUE(%s)", static::tableName(), join(", ", $sql_fields), join(", ", $sql_values));
            echo $sql_string;
            $res =& $core->dbQuery($dbHandle, $sql_string);
        }
          
        $core->dbKillHandle($dbHandle);
        if (isset($res) && $res != NULL && !$core->areErrors())
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    protected function deleteFromDB(Core $core)
    {
        if ($this->id != -1)
        {
            $dbHandle = $core->dbMakeHandle();
            $sql_string = sprintf("DELETE FROM %s WHERE id=%s", static::tableName(), $this->id);
            $res =& $core->dbQuery($dbHandle,$sql_string);

            if (isset($res) && $res != NULL && !$core->areErrors())
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }
        else
        {
            return FALSE;
        }
    }
}

class FieldClubSport2 extends DatabaseItem
{
    protected static $object_name = __CLASS__;
	public $name = NULL;
	public $maxFutureSlots = NULL;

    public function __construct($data = NULL) {
		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->name = $data['name'];
			$this->maxFutureSlots = $data['maxFutureSlots'];
		}
    }

    public function save(Core $core)
    {
        $key_value_pairs = array( "name" => $this->name, "maxFutureSlots" => $this->maxFutureSlots);
        return $this::saveToDB($core, $key_value_pairs);
    }

    public function delete(Core $core)
    {
        return $this::deleteFromDB($core);
    }
}
*/

class FieldClub
{
	protected $core;


	function __construct(Core $core)
	{
		$this->core = $core;
	}
	
	// Dealing with sports of the field club
	
	function addSportDB($name,$maxFutureSlots)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,sprintf("INSERT INTO FieldClubSports(name,maxFutureSlots) VALUES(%s,%s)",$dbHandle->quote($name),$dbHandle->quote($maxFutureSlots)));
		$this->core->dbKillHandle($dbHandle);
		return $res;
	}
	
	function updateSportDB($id,$name,$maxFutureSlots)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,sprintf("UPDATE FieldClubSports SET name=%s,maxFutureSlots=%s WHERE id=%s",$dbHandle->quote($name),$dbHandle->quote($maxFutureSlots),$dbHandle->quote($id)));
		$this->core->dbKillHandle($dbHandle);
		return $res;
	}
	
	function getSportDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubSports " . ($searchString == NULL ? "" : "WHERE $searchString"));
		return $res;
	}
	
	function deleteSportDB($id)
	{
		$res =& $this->getCourtDB("usedBySports LIKE '%$id%'");
		if ($res && $res->numRows() != 0)
		{
			while ($data = $res->fetchRow())
			{
				if (in_array($id,split(",",$data['usedBySports'])))
				{
					trigger_error("Can't remove sport '$id' since it has court '{$data['name']}' assigned to it.", E_USER_ERROR);
					return FALSE;
				}
			}
		}
		$res =& $this->getTeamDB("sportId='$id'");
		if ($res && $res->numRows() != 0)
		{
			$data = $res->fetchRow();
			trigger_error("Can't delete sport '$id' since it has team '{$data['name']}' assigned to it.", E_USER_ERROR);
			return FALSE;
		}

		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubSports WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove fieldclub sport $id");

		if (!$res)
			return FALSE;
		return TRUE;
	}
	
	// Dealing with courts available in the field club
	
	function addCourtDB($name, $usedBySports, T_Time $openFrom, T_Time $openUntil, $slotDuration)
	{
		foreach ($usedBySports as $usedBySport)
		{
			$fieldClubSport = new FieldClubSport($this->core, $usedBySport);
			if (!$fieldClubSport->loaded())
			{
				trigger_error("Can't add court $name as sport $usedBySport doesn't exist.", E_USER_ERROR);
				return FALSE;
			}
		}
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,
                sprintf("INSERT INTO FieldClubCourts(name,usedBySports,openFrom,openUntil,slotDuration) VALUES(%s,%s,%s,%s,%s)",
                    $dbHandle->quote($name),$dbHandle->quote(join(",",$usedBySports)),$dbHandle->quote($openFrom),$dbHandle->quote($openUntil),
                    $dbHandle->quote($slotDuration)));
		return $res;
	}
	
	function updateCourtDB($name, $usedBySports, T_Time $openFrom, T_Time $openUntil, $slotDuration, $id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		foreach ($usedBySports as $usedBySport)
		{
			$fieldClubSport = new FieldClubSport($this->core, $usedBySport);
			if (!$fieldClubSport->loaded())
			{
				trigger_error("Can't add court $name as sport $usedBySport doesn't exist.", E_USER_ERROR);
				return FALSE;
			}
		}
		$res =& $this->core->dbQuery($dbHandle,sprintf("UPDATE FieldClubCourts SET name=%s,usedBySports=%s,openFrom=%s,openUntil=%s,slotDuration=%s WHERE id=%s",$dbHandle->quote($name),$dbHandle->quote(join(",",$usedBySports)),$dbHandle->quote($openFrom),$dbHandle->quote($openUntil),$dbHandle->quote($slotDuration), $id));
		return $res;
	}
	
	function getCourtDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubCourts " . ($searchString == NULL ? "" : "WHERE $searchString")." ORDER BY name");
	}
	
	function deleteCourtDB($id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubCourts WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove fieldclub court $id");
		
		if (!$res)
			return FALSE;
		return TRUE;
	}
	
	function addCourtOverlapDB($courtA, $courtB)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,sprintf("INSERT INTO FieldClubCourtsOverlap(courtA,courtB) VALUES(%s,%s)",$dbHandle->quote($courtA),$dbHandle->quote($courtB)));
		return $res;
	}
	
	function updateCourtOverlapDB($courtA, $courtB, $id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,sprintf("UPDATE FieldClubCourtsOverlap SET courtA=%s,courtA=%s WHERE id=%s",$dbHandle->quote($courtA),$dbHandle->quote($courtB),$id));
		return $res;
	}
	
	function getCourtOverLapDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubCourtsOverlap " . ($searchString == NULL ? "" : "WHERE $searchString"));
	}
	
	function deleteCourtOverlapDB($id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubCourtsOverlap WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove fieldclub court overlap $id");
		
		if (!$res)
			return FALSE;
		return TRUE;
	}

	

	// Dealing with teams at field club

	function addTeamDB($name, $sportId)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->getSportDB("id='$sportId'");
		if ($res && $res->numRows() != 1)
		{
			trigger_error("Can't add team $name as sport $sportId doesn't exist", E_USER_ERROR);
			return FALSE;
		}
		else
		{
			$res =& $this->core->dbQuery($dbHandle,sprintf("INSERT INTO FieldClubTeams(name,sportId) VALUES(%s,%s)",$dbHandle->quote($name),
									$dbHandle->quote($sportId)));
			return TRUE;
		}
		return FAlSE;
	}
	
	function updateTeamDB($name, $sportId, $id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res = $this->getSportDB("id='$sportId'");
		if ($res && $res->numRows() != 1)
		{
			trigger_error("Can't update team $name as sport $sportId doesn't exist", E_USER_ERROR);
			return FALSE;
		}
		else
		{
			$res =& $this->core->dbQuery($dbHandle,sprintf("UPDATE FieldClubTeams SET name=%s,sportId=%s WHERE id=%s",$dbHandle->quote($name),
									$dbHandle->quote($sportId),$dbHandle->quote($id)));
			if ($res)
				return TRUE;
		}
		return FAlSE;
	}
	function getTeamDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubTeams " . ($searchString == NULL ? "" : "WHERE $searchString")." ORDER BY sportId");
	}
	
	function deleteTeamDB($id)
	{
		$res =& $this->getUserDB("captainOfTeams like '%$id%'");
		if ($res && $res->numRows() != 0)
		{
			while ($data = $res->fetchRow())
			{
				if (in_array($id,split(",",$data['captainOfTeams'])))
				{
					trigger_error("Can't remove team '$id' since it has user '{$data['crsid']}' assigned to it.", E_USER_ERROR);
					return FALSE;
				}
			}
		}
		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubTeams WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove fieldclub court $id");
		if ($res)
			return TRUE;
	}
	
	// Dealing with users field club

	function addUserDB($username, $password, $accessLevel, $captainOfTeams, $userType, $userStatus, $loginType, $userNotes, $email, $name)
	{

		foreach ($captainOfTeams as $team)
		{
			$res =& $this->getTeamDB("id='$team'");
			if ($res && $res->numRows() != 1)
			{
				trigger_error("Can't add user $crsId as team $team doesn't exist", E_USER_ERROR);
				return FALSE;
			}
		}

		$res =& $this->getUserDB("username='$username'");
		if ($res && $res->numRows() > 0)
		{
			trigger_error("User $username already exists.", E_USER_ERROR);
			return FALSE;
		}
		
		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("INSERT INTO FieldClubUsers(username,password,accessLevel,captainOfTeams,userType,userStatus,loginType,userNotes,email,name) VALUES(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",$dbHandle->quote($username),$dbHandle->quote($password),$dbHandle->quote($accessLevel), $dbHandle->quote(join(",",$captainOfTeams)),$dbHandle->quote($userType),$dbHandle->quote($userStatus),$dbHandle->quote($loginType),$dbHandle->quote($userNotes),$dbHandle->quote($email), $dbHandle->quote($name));

		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't add user $username");

		return $res;
	}
	
	function getUserDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubUsers " . ($searchString == NULL ? "" : "WHERE $searchString"));
	}
	
	function deleteUserDB($id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubUsers WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove user $id");
		
		if (!$res)
			return FALSE;
		return TRUE;
	}

	function updateUserDB($username,$password,$accessLevel,$captainOfTeams,$userType,$userStatus,$loginType,$userNotes,$email,$name,$id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		foreach ($captainOfTeams as $team)
		{
			$res =& $this->getTeamDB("id='$team'");
			if ($res && $res->numRows() != 1)
			{
				trigger_error("Can't update user $crsid as team $team doesn't exist", E_USER_ERROR);
				return FALSE;
			}
		}

		$sql = sprintf("UPDATE FieldClubUsers SET username=%s,password=%s,accessLevel=%s,captainOfTeams=%s,userType=%s,userStatus=%s,loginType=%s,userNotes=%s,email=%s,name=%s WHERE id=%s",$dbHandle->quote($username),$dbHandle->quote($password),$dbHandle->quote($accessLevel), $dbHandle->quote(join(",",$captainOfTeams)),$dbHandle->quote($userType),$dbHandle->quote($userStatus),$dbHandle->quote($loginType),$dbHandle->quote($userNotes), $dbHandle->quote($email), $dbHandle->quote($name), $dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't update user $id");

		if (!$res)
			return FALSE;
		return TRUE;
	}

	// Dealing with bookings in field club

	function addBookingDB($userId, $sportId, $courtId, T_DateTime $startDateTime, T_DateTime $endDateTime, $teamId = -1, $notes)
	{
		$fieldClubUser = new FieldClubUser($this->core, $userId);
		if (!$fieldClubUser->loaded())
			return FALSE;
		$fieldClubSport = new FieldClubSport($this->core, $sportId);
		if (!$fieldClubSport->loaded())
			return FALSE;
		$fieldClubCourt = new FieldClubCourt($this->core, $courtId);
		if (!$fieldClubCourt->loaded())
			return FALSE;

		// check if the user is captain of this team
		if ($teamId > -1)
		{
			$team = new FieldClubTeam($this->core, $teamId);
			if (!$team->loaded())
				return FALSE;
			if (!$fieldClubUser->isCaptainOfTeam($teamId))
			{
				trigger_error("{$fieldClubUser} isn't captain of the {$team} team", E_USER_ERROR);
				return FALSE;
			}
		}

		if (!$fieldClubCourt->isUsedBySport($sportId))
		{
			trigger_error("{$fieldClubCourt->name} is not used for {$fieldClubSport->name}", E_USER_ERROR);
			return FALSE;
		}

		if (!($fieldClubCourt->openFrom->value() <= $startDateTime->getTime()->value() && $endDateTime->getTime()->value() <= $fieldClubCourt->openUntil->value()))
		{
			trigger_error("{$fieldClubCourt->name} is not open at times {$startDateTime->getTime()}-{$endDateTime->getTime()} (open {$fieldClubCourt->openFrom}-{$fieldClubCourt->openUntil})", E_USER_ERROR);
			return FALSE;
		}

		if ($startDateTime->value < time())
		{
			trigger_error("You can only book slots in the future.",E_USER_ERROR);
			return FALSE;
		}

		if ($fieldClubUser->accessLevel == FieldClubUser::$ACCESSLEVEL_ADMIN || 
			$fieldClubUser->accessLevel == FieldClubUser::$ACCESSLEVEL_PLODGE ||
			($teamId > -1 ? $fieldClubUser->isCaptainOfTeam($teamId) : FALSE) ||
			$fieldClubUser->userType == FieldClubUser::$USERTYPE_FELLOW)
		{
			// don't need to check slot duration or current bookings for superuser or captain of the team
		}
		else
		{
			// check slot duration
			if ($fieldClubCourt->slotDuration !== 0)
			{
				$duration = $endDateTime->value - $startDateTime->value;

				if ($duration > $fieldClubCourt->slotDuration)
				{
					trigger_error("{$fieldClubCourt->name} only allows slots of {$fieldClubCourt->slotDurationMins()} mins.", E_USER_ERROR);
					return FALSE;
				}
			}

			// normal users can only book on slot per sport in advance
			$res =& $this->getBookingDB("startDateTime > NOW() && sportId='$sportId' && userId='{$fieldClubUser->getId()}' && teamId='-1'");
			if ($res && $res->numRows() == $fieldClubSport->maxFutureSlots)
			{
				$bookingInfo = array();
				while ($bookingData = $res->fetchRow())
				{
					$fieldClubBooking = new FieldClubBooking($this->core, null,$bookingData);
					$bookingInfo[] = "{$fieldClubBooking->getDate()} {$fieldClubBooking->getTimeSpan()} ({$fieldClubCourt->name})";
				}
				trigger_error("You already have the maximum number of bookings for {$fieldClubSport}:<br />".join("<br />",$bookingInfo), E_USER_ERROR);
				return FALSE;
			}

			/*
			 * LEGACY CODE:
			 * Use this snippet to produce a "current week" behaviour where users may only book for the current week.
			// and only for current week
			if ($startDateTime->value > strtotime("next Monday"))
			{
				trigger_error("You can only book for this week.");
				return FALSE;
			}
			*/

			// Slots may only be booked within a running week
			if ($startDateTime->value > strtotime("now +1 week")) {
				trigger_error(sprintf("You can only book within the current running week, so for slots up until %s", new T_DateTime(NULL, strtotime("now +1 week"))) );
				return FALSE;
			}
		}

		// check if this court overlaps with another court which is booked for this timeslot
		foreach ($fieldClubCourt->getOverlappingCourts() as $overlapping_court_id) {
			$dbHandle = $this->core->dbMakeHandle();
			$res = $this->getBookingDB(sprintf("!((startDateTime > %s && startDateTime >= %s) || (endDateTime <= %s && endDateTime < %s))
												&& courtId=%s",$dbHandle->quote($startDateTime->mysqlFormat()),$dbHandle->quote($endDateTime->mysqlFormat()),
												$dbHandle->quote($startDateTime->mysqlFormat()),$dbHandle->quote($endDateTime->mysqlFormat()),
												$overlapping_court_id));
			if ($res->numRows() > 0)
			{
				$booking = new FieldClubBooking($this->core,null,$res->fetchRow());
				$overlapping_court = new FieldClubCourt($this->core, $overlapping_court_id);
				trigger_error(sprintf("%s physically overlaps with %s which is already booked for this time slot.", $fieldClubCourt, $overlapping_court));
				return FALSE;
			}
		}

		// check if the court has already been booked at this time

		$dbHandle = $this->core->dbMakeHandle();
		$res = $this->getBookingDB(sprintf("!((startDateTime > %s && startDateTime >= %s) || (endDateTime <= %s && endDateTime < %s))
											&& courtId=%s",$dbHandle->quote($startDateTime->mysqlFormat()),$dbHandle->quote($endDateTime->mysqlFormat()),
											$dbHandle->quote($startDateTime->mysqlFormat()),$dbHandle->quote($endDateTime->mysqlFormat()),
											$dbHandle->quote($fieldClubCourt->getId())));
		if ($this->core->areErrors())
			return FALSE;

		if ($res->numRows() > 0)
		{
			$booking = new FieldClubBooking($this->core,null,$res->fetchRow());
			trigger_error("There is already a slot booking from {$booking->startDateTime->getTime()} to {$booking->endDateTime->getTime()} on {$booking->startDateTime->getDate()} which clashes with this booking.");
			return FALSE;
		}

		// When normal user's book for themselves they can't be playing on more than one place at once
		if ($fieldClubUser->accessLevel == FieldClubUser::$ACCESSLEVEL_USER && $teamId == FieldClubTeam::$TEAM_SELF)
		{
			$res = $this->getBookingDB(sprintf("!((startDateTime > %s && startDateTime >= %s) || (endDateTime <= %s && endDateTime < %s))
											&& userId=%s",$dbHandle->quote($startDateTime->mysqlFormat()),$dbHandle->quote($endDateTime->mysqlFormat()),
											$dbHandle->quote($startDateTime->mysqlFormat()),$dbHandle->quote($endDateTime->mysqlFormat()),
											$dbHandle->quote($fieldClubUser->getId())));
			if ($this->core->areErrors())
				return FALSE;


			if ($res->numRows() > 0)
			{
				$currentBooking = new FieldClubBooking($this->core,null,$res->fetchRow());
				trigger_error("You already have a booking at the time chosen, you can't be playing in two places at once! (other booking is {$currentBooking} playing {$currentBooking->getSport()} in the {$currentBooking->getCourt()})");
				return FALSE;
			}
		}

		// All's good, go ahead and book the slot!

		$sql = sprintf("INSERT INTO FieldClubBookings(sportId,userId,courtId,startDateTime,endDateTime,teamId,notes) VALUES(%s,%s,%s,%s,%s,%s,%s)",$dbHandle->quote($sportId),$dbHandle->quote($userId),$dbHandle->quote($courtId),$dbHandle->quote($startDateTime->mysqlFormat()),$dbHandle->quote($endDateTime->mysqlFormat()),$dbHandle->quote($teamId), $dbHandle->quote($notes));
	
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't add court booking.", E_USER_ERROR);
		if (!$res)
			return FALSE;
		return TRUE;
	}
	
	function getBookingDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubBookings " . ($searchString == NULL ? "" : "WHERE $searchString"));
	}
	
	function deleteBookingDB($id)
	{
		$fieldClubBooking = new FieldClubBooking($this->core, $id);
		if (!$fieldClubBooking->loaded())
			return FALSE;

		if ($fieldClubBooking->startDateTime->value < time())
		{
			trigger_error("You can't delete past bookings.");
			return FALSE;
		}

		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubBookings WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove booking $id");
		
		if (!$res)
			return FALSE;
		return TRUE;
	}

	public function log($message)
	{
		$fieldClubAccess = new FieldClubAccess($this->core);
		$username = ($fieldClubAccess->isLoggedIn() ? $fieldClubAccess->username : "Unknown user");
		$userId = ($fieldClubAccess->isLoggedIn() ? $fieldClubAccess->getId() : -1);
		$dbHandle = $this->core->dbMakeHandle();
		$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$sql = sprintf("INSERT INTO FieldClubLog(hostname,username,userId,message,dateTime) VALUES(%s,%s,%s,%s,NOW())",$dbHandle->quote($hostname),$dbHandle->quote($username),$dbHandle->quote($userId),$dbHandle->quote($message));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't make log entry.");
		if (!$res)
			return FALSE;
		return TRUE;
	}

	function getLog($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,sprintf("SELECT * FROM FieldClubLog %s ORDER BY id DESC LIMIT 50",($searchString != NULL ? "WHERE userName LIKE '%$searchString%'" : "")));
	}
	
	function addUserRequestDB($username)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,sprintf("INSERT INTO FieldClubUserRequests(username,dateTime) VALUES(%s,NOW())",$dbHandle->quote($username)));
		$this->core->dbKillHandle($dbHandle);
		return $res;
	}
	
	function deleteUserRequestDB($id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubUserRequests WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove user request $id");

		if (!$res)
			return FALSE;
		return TRUE;
	}
	
	function getUserRequestDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubUserRequests " . ($searchString == NULL ? "" : "WHERE $searchString"));
		return $res;
	}
	
	function getKitOrderDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubKitOrders " . ($searchString == NULL ? "" : "WHERE $searchString"));
	}
	
	function getPlacedKitOrderDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubPlacedKitOrders " . ($searchString == NULL ? "" : "WHERE $searchString"));
	}

	function getKitOrderSizeDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubKitOrderSizes " . ($searchString == NULL ? "" : "WHERE $searchString"));
	}

	function getKitOrderItemDB($searchString = NULL)
	{
		$dbHandle = $this->core->dbMakeHandle();
		return $this->core->dbQuery($dbHandle,"SELECT * FROM FieldClubKitOrderItems " . ($searchString == NULL ? "" : "WHERE $searchString"));
	}
				
	function addPlacedKitOrderDB($userId,$kitOrderId,$items,$status) {
		$dbHandle = $this->core->dbMakeHandle();
		$res =& $this->getUserDB("id='{$userId}'");
		if ($res && $res->numRows() != 1)
		{
			trigger_error("Can't add kit order as user $userId doesn't exist", E_USER_ERROR);
			return FALSE;
		}
		else
		{
			$res =& $this->core->dbQuery($dbHandle,sprintf("INSERT INTO FieldClubPlacedKitOrders(userId,kitOrderId,orderedItems,status) VALUES(%s,%s,%s,%s)",$dbHandle->quote($userId),$dbHandle->quote($kitOrderId),$dbHandle->quote($items),$dbHandle->quote($status)));
			return TRUE;
		}
		return FAlSE;

	}
	
	function deletePlacedKitOrderDB($id)
	{
		$dbHandle = $this->core->dbMakeHandle();
		$sql = sprintf("DELETE FROM FieldClubPlacedKitOrders WHERE id=%s",$dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't remove placed kit order $id");

		if (!$res)
			return FALSE;
		return TRUE;
	}
	
	function updatePlacedKitOrderDB($id, $userId, $kitOrderId, $items, $status) 
	{
		$dbHandle = $this->core->dbMakeHandle();

		$sql = sprintf("UPDATE FieldClubPlacedKitOrders SET userId=%s, kitOrderId=%s, orderedItems=%s, status=%s WHERE id=%s",$dbHandle->quote($userId),$dbHandle->quote($kitOrderId),$dbHandle->quote($items), $dbHandle->quote($status), $dbHandle->quote($id));
		$res =& $this->core->dbQuery($dbHandle,$sql,"Couldn't update kit order $id");

		if (!$res)
			return FALSE;
		return TRUE;
	}
}

class FieldClubUser extends FieldClub
{

	public static $ACCESSLEVEL_ADMIN = 0; // Admins
	public static $ACCESSLEVEL_PLODGE = 1; // Porter's Lodge
	public static $ACCESSLEVEL_USER = 2; // Captains, Fellows and Students
	public static $ACCESSLEVEL__MAP = array(0 => "Admin", 1 => "Porter's lodge", 2 => "General user");

	public static $USERTYPE_STUDENT = 0;
	public static $USERTYPE_FELLOW = 1;
	public static $USERTYPE__MAP = array(0 => "Student user",1 => "Fellow");

	public static $LOGINTYPE_ALTERNATIVE_LOGIN = 0;
	public static $LOGINTYPE_RAVEN = 1;
	public static $LOGINTYPE__MAP = array(0 => "Alternative login", 1 => "Raven");

	public static $USERSTATUS_GREEN = 0;
	public static $USERSTATUS_YELLOW = 1;
	public static $USERSTATUS_RED = 2;
	public static $USERSTATUS__MAP = array (0 => "green", 1 => "yellow", 2 => "red");

	private $id = NULL;
	public $accessLevel = NULL;
	public $name = NULL;
	public $username = NULL;
	public $password = NULL;
	public $email = NULL;
	private $passwordEncoded = NULL;
	public $captainOfTeams = array();
	public $userType = NULL;
	public $userStatus = NULL;
	public $userNotes = '';

	public function __toString()
	{
		return $this->username;
	}

	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		// default variables:
		$this->userType = FieldClubUser::$USERTYPE_STUDENT;
		$this->loginType = FieldClubUser::$LOGINTYPE_RAVEN;
		$this->userStatus = FieldClubUser::$USERSTATUS_GREEN;
		$this->accessLevel = FieldClubUser::$ACCESSLEVEL_USER;

		if ($id != NULL)
		{
			$res =& $this->getUserDB("id='$id'");
			if ($res && $res->numRows() != 1)
			{
				trigger_error("User $id doesn't exist.", E_USER_ERROR);
			}
			else
			{
				$data = $res->fetchRow();
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->accessLevel = $data['accessLevel'];
			$this->captainOfTeams = $data['captainOfTeams'] != '' ? split(",",$data['captainOfTeams']) : array();
			$this->username = $data['username'];
			$this->passwordEncoded = $data['password'];
			$this->userType = $data['userType'];
			$this->userStatus = $data['userStatus'];
			$this->userNotes = $data['userNotes'];
			$this->loginType = $data['loginType'];
			$this->email = $data['email'];
			$this->name = $data['name'];
		}
	}

	public function getId()
	{
		return $this->id;
	}

	public function isCaptainOfTeam($teamId)
	{
		return in_array($teamId,$this->captainOfTeams);
	}

	public function isCaptainOfTeamWhichPlaysSport($sportId)
	{
		foreach ($this->captainOfTeams as $teamId)
		{
			$team = new FieldClubTeam($this->core, $teamId);
			if ($team->isSport($sportId))
				return TRUE;
		}
		return FALSE;
	}

	public function isAdmin()
	{
		return $this->accessLevel == FieldClubUser::$ACCESSLEVEL_ADMIN;
	}

	public function userStatusIs($status)
	{
		return $this->userStatus == $status;
	}

	public function userTypeIs($type)
	{
		return $this->userType == $type;
	}
	
		public function loginTypeIs($type)
		{
			return $this->loginType == $type;
		}

	public function encodePassword($string)
	{
		return md5($this->core->PASSWORD_SALT . $string);
	}

	public function loaded()
	{
		return $this->id != NULL;
	}

	public function hasAccessLevel($accessLevel)
	{
		return $this->accessLevel <= $accessLevel;
	}

	public function save()
	{	   
		if ($this->loginTypeIs(FieldClubUser::$LOGINTYPE_ALTERNATIVE_LOGIN) && $this->email == NULL)
		{
			trigger_error("Alternative user accounts require supplying an email adress.");
			return FALSE;
		}
		else if ($this->loginTypeIs(FieldClubUser::$LOGINTYPE_RAVEN) && $this->email == NULL)
		{
			if ($this->username != NULL)
				$this->email = $this->username."@cam.ac.uk";
			else
			{
				trigger_error("You must supply a username for Raven users.");
				return FALSE;
			}

			if ($this->name == NULL)
			{
				$this->name = Toolkit::nameFromLdapLookup($this->username);
			}
		}

		if ($this->password != NULL || $this->passwordEncoded == NULL)	   
		{
			$this->passwordEncoded = $this->encodePassword($this->password);
	   	}

		if ($this->loaded())
		{
			return $this->updateUserDB($this->username,$this->passwordEncoded,$this->accessLevel,$this->captainOfTeams,$this->userType,$this->userStatus,$this->loginType,$this->userNotes,$this->email,$this->name,$this->id);		
		}
		else
		{
			return $this->addUserDB($this->username,$this->passwordEncoded,$this->accessLevel,$this->captainOfTeams,$this->userType,$this->userStatus,$this->loginType,$this->userNotes,$this->email,$this->name);		
		}
	}

	public function delete()
	{
		return $this->deleteUserDB($this->id);
	}
}

class FieldClubCourt extends FieldClub
{
    protected static $object_name = __CLASS__;
    
	public $name = NULL;
	public $usedBySports = array();
	public $openFrom = NULL;
	public $openUntil = NULL;
	public $slotDuration = NULL;
	public $overlapsWith = NULL; // Only used for setting new overlaps, should always request newest list of overlaps from GetOverlappingCourts()
	public $statusOverride = NULL;
    public $coordinates = NULL;

    public function __construct(Core $core, $id = NULL, $data = NULL)
    {
        parent::__construct($core);

        if ($id != NULL)
        {
            $res =& $this->getCourtDB("id=$id");
            if (isset($res) && $res->numRows() != 0)
            {
                $data = $res->fetchRow();
            }
            else
            {
                trigger_error("Court $id doesn't exist.", E_USER_ERROR);
            }
        }

        if ($data != NULL)
        {
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->openFrom = new T_Time($data['openFrom']);
            $this->openUntil = new T_Time($data['openUntil']);                                  
            $this->usedBySports = ($data['usedBySports'] != NULL ? split(",",$data['usedBySports']) : "");
            $this->slotDuration = $data['slotDuration'];
            $this->overlapsWith = $this->GetOverlappingCourts();
            $this->statusOverride = (int)$data['statusOverride'];
            $this->coordinates = ($data['coordinates'] != NULL ? split(";",$data['coordinates']) : "");
        }
    }

	public function __toString()
	{ 
		return (string)$this->name;
	}

	public function getDefiniteName()
	{
		$matches = 0;
		preg_match_all('/[0-9]/', $this->name, $matches);
		return (count($matches[0]) > 0 ? (string)$this->name : sprintf("The %s", (string)$this->name));
	}
	
	public function slotDurationMins()
	{
		return $this->slotDuration/60;
	}

	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function getId()
	{
		return $this->id;
	}

	public function isUsedBySport($sportId)
	{
		return in_array($sportId,$this->usedBySports);
	}

	public function getOverlappingCourts()
	{
		if (!$this->loaded()) {
			return array();
		}

		$res =& $this->getCourtOverlapDB(sprintf("courtA='%d' OR courtB='%d'", $this->id, $this->id));
		if (isset($res) && $res && $res->numRows() != 0)
		{
			$overlapping_courts_ids = array();
			while( $data = $res->fetchRow() )
			{
				$court_overlap = new FieldClubCourtOverlapPair($this->core, null, $data);
				if ($court_overlap->courts[0] != $this->id) {
					$overlapping_courts_ids[] = $court_overlap->courts[0];
				}
				else {
					$overlapping_courts_ids[] = $court_overlap->courts[1];
				}
			}
			return $overlapping_courts_ids;
		}
		else
		{
			return array();
		}
	}

	public function getCourtStateBetween(T_DateTime $startDateTime, T_DateTime $endDateTime) {
		if ($this->statusOverride != -1)
		{
			return $this->statusOverride;
		}
			
		// Check if this court is booked
		$res = $this->getBookingDB(sprintf("!((startDateTime > '%s' && startDateTime >= '%s') || (endDateTime <= '%s' && endDateTime < '%s'))
											&& courtId=%s",$startDateTime->mysqlFormat(),$endDateTime->mysqlFormat(),
											$startDateTime->mysqlFormat(),$endDateTime->mysqlFormat(),
											$this->getId()));
		if ($res && $res->numRows() != 0) {
			return CourtState::Taken;
		}

		// Check if any of the overlapping courts are booked
		foreach ($this->getOverlappingCourts() as $overlapping_court_id) {
			$res = $this->getBookingDB(sprintf("!((startDateTime > '%s' && startDateTime >= '%s') || (endDateTime <= '%s' && endDateTime < '%s'))
												&& courtId=%s",$startDateTime->mysqlFormat(),$endDateTime->mysqlFormat(),
												$startDateTime->mysqlFormat(),$endDateTime->mysqlFormat(),
												$overlapping_court_id));
			if ($res && $res->numRows() != 0) {
				return CourtState::TakenByOverlap;
			}
		}
			
		return CourtState::Free;
	}

	public function getOverlappingBooking(T_DateTime $startDateTime, T_DateTime $endDateTime)
	{
		foreach ($this->getOverlappingCourts() as $overlapping_court_id) 
		{
			$res = $this->getBookingDB(sprintf("!((startDateTime > '%s' && startDateTime >= '%s') || (endDateTime <= '%s' && endDateTime < '%s'))
												&& courtId=%s",$startDateTime->mysqlFormat(),$endDateTime->mysqlFormat(),
												$startDateTime->mysqlFormat(),$endDateTime->mysqlFormat(),
												$overlapping_court_id));
			if ($res && $res->numRows() != 0) {
				$overlapping_booking = new FieldClubBooking($this->core, null, $res->fetchRow());
				return $overlapping_booking;
			}
		}
		return NULL;
	}
	
	public function save()
	{
		# Delete all court overlaps and recreate the ones that the user requested
		$res =& $this->getCourtOverlapDB(sprintf("courtA='%d' OR courtB='%d'", $this->id, $this->id));
		if (isset($res) && $res && $res->numRows() != 0)
		{
			$overlapping_courts_ids = array();
			while( $data = $res->fetchRow() )
			{
				$overlap = new FieldClubCourtOverlapPair($this->core, null, $data);
				$overlap->delete();
			}
		}
		foreach ($this->overlapsWith as $new_overlap_id) {
			$overlap = new FieldClubCourtOverlapPair($this->core);
			$overlap->courts = array($this->getId(), $new_overlap_id);
			$overlap->save();
		}

		if (!$this->loaded())
		{
			$ret = $this->addCourtDB($this->name,$this->usedBySports,$this->openFrom,$this->openUntil, $this->slotDuration);
			if ($ret)
				$this->log("Added court {$this}");
			return $ret;
		}
		else
		{
			return $this->updateCourtDB($this->name,$this->usedBySports,$this->openFrom,$this->openUntil,$this->slotDuration,$this->id);
		}
	}
	
	public function delete()
	{
		return $this->deleteCourtDB($this->id);
	}
}

class FieldClubSport extends FieldClub
{
	private $id = NULL;
	public $name = NULL;
	public $maxFutureSlots = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getSportDB("id=$id");
			if (isset($res) && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Sport $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->name = $data['name'];
			$this->maxFutureSlots = $data['maxFutureSlots'];
		}
	}

	public function __toString()
	{
		return $this->name;
	}
	
	public function loaded()
	{
		return ($this->id !== NULL);
	}       

	public function getId()
	{
		return $this->id;
	}

	public function getCourts()
	{
		$fieldClubCourts = array();
		$res =& $this->getCourtDB("usedBySports LIKE '%{$this->getId()}%'");
		if ($res && $res->numRows() != 0)
		{
			while ($data = $res->fetchRow())
			{
				$fieldClubCourt = new FieldClubCourt($this->core,null,$data);
				if ($fieldClubCourt->isUsedBySport($this->getId()))
				{
					$fieldClubCourts[] = $fieldClubCourt;
				}
			}
		}
		return $fieldClubCourts;
	}

	public function save()
	{
		if (!$this->loaded())
		{
			return $this->addSportDB($this->name,$this->maxFutureSlots);
		}
		else
		{
			return $this->updateSportDB($this->id,$this->name,$this->maxFutureSlots);
		}
	}

	public function delete()
	{
		return $this->deleteSportDB($this->id);
	}
	
}

class FieldClubUserRequest extends FieldClub
{
	private $id = NULL;
	public $username = NULL;
	public $dateTime = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getUserRequestDB("id=$id");
			if (isset($res) && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Request $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->username = $data['username'];
			$this->dateTime = new T_DateTime($data['dateTime']);
		}
	}

	public function __toString()
	{
		return $this->username;
	}
	
	public function loaded()
	{
		return ($this->id !== NULL);
	}       

	public function getId()
	{
		return $this->id;
	}

	public function save()
	{
		if (!$this->loaded())
		{
			return $this->addUserRequestDB($this->name);
		}
		else
		{
			return $this->updateUserRequesDB($this->id,$this->name);
		}
	}

	public function delete()
	{
		return $this->deleteUserRequestDB($this->id);
	}
	
}

class FieldClubTeam extends FieldClub
{
	/*
	 *	Hardcoded team ids:
	 *  -1 : Booking for self
	 *	-2 : Booking on behalf on someone else (only admins and plodge can do this)
	 */

    public static $TEAM_SELF = -1;

	private static $hardcodedData = array (
										"-1" => array("id" => "-1", "name" => "yourself", "sportId" => ""),
										"-2" => array("id" => "-2", "name" => "on behalf on someone else", "sportId" => "")
									);

	private $id = NULL;
	public $name = NULL;
	public $sportId = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			if (isset(self::$hardcodedData[$id]))
			{
				$data = self::$hardcodedData[$id];
			}
			else
			{
				$res =& $this->getTeamDB("id=$id");
				if (isset($res) && $res->numRows() != 0)
				{
					$data = $res->fetchRow();
				}
				else
				{
					trigger_error("Team $id doesn't exist.", E_USER_ERROR);
				}
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->name = $data['name'];
			$this->sportId = $data['sportId'];
		}
	}

	public function __toString()
	{
		return $this->name;
	}

	public function getSport()
	{
		return new FieldClubSport($this->core,$this->sportId);
	}

	public function isSport($sportId)
	{
		return $this->sportId == $sportId;
	}
	
	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function isDummyEntry()
	{
		return $this->id < 0;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getCaptain()
	{
		$res =& $this->getUserDB("captainOfTeams LIKE '%{$this->id}%'");
		if ($res && $res->numRows() > 0)
		{
			while ($data = $res->fetchRow())
			{
				$fieldClubUser = new FieldClubUser($this->core,null,$data);
				if ($fieldClubUser->isCaptainOfTeam($this->id));
					return $fieldClubUser;
			}
		}
		return FALSE;
	}

	public function save()
	{
		if (!$this->isDummyEntry())
		{
			if (!$this->loaded())
			{
				return $this->addTeamDB($this->name,$this->sportId);
			}
			else
			{
				return $this->updateTeamDB($this->name,$this->sportId,$this->id);
			}
		}
		else
		{
			return FALSE;
		}
	}

	public function delete()
	{
		if (!$this->isDummyEntry())
		{
			return $this->deleteTeamDB($this->id);
		}
		else
		{
			return FALSE;
		}
	}
}

class FieldClubBooking extends FieldClub
{
	private $id = NULL;
	public $sportId = NULL;
	public $userId = array();
	public $courtId = NULL;
	public $teamId = NULL;
	public $startDateTime = NULL;
	public $endDateTime = NULL;
	public $notes = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getBookingDB("id=$id");
			if (isset($res) && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Booking $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->sportId = $data['sportId'];
			$this->userId = $data['userId'];
			$this->courtId = $data['courtId'];
			$this->teamId = $data['teamId'];
			$this->startDateTime = new T_DateTime($data['startDateTime']);
			$this->endDateTime = new T_DateTime($data['endDateTime']);
			$this->notes = $data['notes'];
		}
	}

	public function __toString()
	{
		return $this->getTimeSpan()." ".$this->getDate();
	}

	public function getDate()
	{
		if (is_object($this->startDateTime))
			return $this->startDateTime->getDate();
	}

	public function getTimeSpan()
	{
		if (is_object($this->startDateTime))
			return $this->startDateTime->getTime()."-".$this->endDateTime->getTime();
	}

	public function getSport()
	{
		return new FieldClubSport($this->core, $this->sportId);
	}

	public function getCourt()
	{
		return new FieldClubCourt($this->core, $this->courtId);
	}

	public function timeSpanIsDuring(T_DateTime $startDateTime, T_DateTime $endDateTime)
	{
		return !(
				($startDateTime->value < $this->startDateTime->value && $endDateTime->value <= $this->startDateTime->value)
				||
				($this->endDateTime->value <= $startDateTime->value && $this->endDateTime->value < $endDateTime->value)
				);
	}

	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function getId()
	{
		return $this->id;
	}

	public function save()
	{
		if (!$this->loaded())
		{
			$ret = $this->addBookingDB($this->userId, $this->sportId, $this->courtId, $this->startDateTime, $this->endDateTime, $this->teamId, $this->notes);
			if ($ret)
			{
				$sport = new FieldClubSport($this->core,$this->sportId);
				$court = new FieldClubCourt($this->core,$this->courtId);
				$this->log("Added new booking {$this} (playing {$sport} in the {$court})");
			}
			return $ret;
		}
		else
		{
			trigger_error("Bookings can't be amended. You should delete this booking and create a new one.", E_USER_ERROR);
			return FALSE;
			//$this->updateBooking($this->userId, $this->sportId, $this->courtId, $this->startTime, $this->endTime, $this->teamId)
		}
	}
	
	public function delete()
	{
		if (!$this->loaded())
			return FALSE;

		$fieldClubAccess = new FieldClubAccess($this->core);

		if (!$fieldClubAccess->isLoggedIn())
		{
			trigger_error("You need to be logged in to delete bookings.");
			return FALSE;
		}
		else if (!($fieldClubAccess->getId() == $this->userId || $fieldClubAccess->isAdmin()))
		{
			trigger_error("You don't have rights to delete this booking.");
			return FALSE;
		}
		else
		{
			$ret = $this->deleteBookingDB($this->id);
			if ($ret)
			{
				$sport = new FieldClubSport($this->core,$this->sportId);
				$court = new FieldClubCourt($this->core,$this->courtId);
				$this->log("Removed booking {$this} (playing {$sport} in the {$court})");
			}
			
			return $ret;
		}
	}
}

class FieldClubCourtOverlapPair extends FieldClub
{
	private $id = NULL;
	public $courts = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getCourtOverlapDB("id=$id");
			if (isset($res) && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Court overlap pair $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->courts = array($data['courtA'], $data['courtB']);
		}
	}

	public function __toString()
	{
		return sprintf("court overlap for %s and %s", $this->courts[0], $this->courts[1]);
	}
	
	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function getId()
	{
		return $this->id;
	}

	public function save()
	{
		# CourtOverlap pairs should also be arranged to that the smalles court index goes first
		sort($this->courts);
		if (!$this->loaded())
		{
			$ret = $this->addCourtOverlapDB($this->courts[0],$this->courts[1]);
			if ($ret)
				$this->log("Added court overlap pair {$this}");
			return $ret;
		}
		else
		{
			return $this->updateCourtOverlapDB($this->courts[0],$this->courts[1]);
		}
	}
	
	public function delete()
	{
		return $this->deleteCourtOverlapDB($this->id);
	}
}

class FieldClubAccess extends FieldClubUser
{
	private $fieldClubUser = NULL;

	public static $PROGRAMLEVEL_STATUS_CHANGE = 1;
	public static $PROGRAMLEVEL_GENERAL_ADMIN = 0;
	
	public function __construct(Core $core, $username = NULL, $password = NULL, $ravenLogin = FALSE)
	{
		parent::__construct($core);
		
		if ($username != NULL)
		{
			$res = NULL;
			if (!$ravenLogin)
			{
				$res =& $this->getUserDB("username='$username' && password='{$this->encodePassword($password)}' && loginType='".FieldClubUser::$LOGINTYPE_ALTERNATIVE_LOGIN."'");
			}
			else
			{
				$res =& $this->getUserDB("username='$username' && loginType='".FieldClubUser::$LOGINTYPE_RAVEN."'");
			}

			if ($res && $res->numRows() == 1)
			{
				$data = $res->fetchRow();
				parent::__construct($core, $data['id']);

				$this->logIn($this->username,$data['id']);
			}
			else if ($ravenLogin)
			{
				// we automatically add accounts for raven users (if they are from Trinity)
				if (!Toolkit::crsidIsTrinity($username))
				{
					trigger_error("Only members of Trinity can login using their Raven account. You may be seeing this because your Raven-id is not affliated with Trinity. To request a user please <a href='?page=requestNewUser&username=$username'>click here</a>.");
					parent::__construct($core);
				}
				else
				{
					parent::__construct($core);
					$this->username = $username;
					$this->userType = FieldClubUser::$USERTYPE_STUDENT;
					$this->loginType = FieldClubUser::$LOGINTYPE_RAVEN;
					if ($this->save())
					{
						$this->log("Added new Raven user '$username'");
						/* this is slightly dangerous since we might cause the constructor to loop, though 
						   there should be a pretty good chance that the user will be only be able to be added once
						 */
						self::__construct($core, $username, $password, $ravenLogin);
					}
				}
			}
			else
			{
				trigger_error("Username/password incorrect.");
				$this->log("Failed login attempt '{$username}'");
				parent::__construct($core);
			}
		}
		else if (isset($_SESSION['userId']))
		{
			parent::__construct($core, $_SESSION['userId']);

		}

		// Even users who haven't logged in will have preferences :)
		// these variables could be saved in the database eventually
		if (isset($_GET['showCourt']))
			$_SESSION['showCourt'] = $_GET['showCourt'];

		if (isset($_GET['weekOffset']))
			$_SESSION['weekOffset'] = $_GET['weekOffset'];
			
		$this->showSavedMessages();
	}

	public function preferredWeekOffset()
	{
		if (isset($_SESSION['weekOffset']))
			return $_SESSION['weekOffset'];
		else
			return 0;
	}

	public function preferredCourt()
	{
		if (isset($_SESSION['showCourt']))
		{
			return $_SESSION['showCourt'];
		}
		else
		{
			return -1;
		}
	}

	public function hasAccessTo($program)
	{
		return $this->isLoggedIn() && $program >= $this->accessLevel;
	}

	public function hasAcceptedTAndC($newValue = NULL)
	{
		if ($newValue != NULL)
			$_SESSION['acceptedTAndC'] = $newValue;

		//Only students have to accept T&C
		if ($this->userType == FieldClubUser::$USERTYPE_STUDENT && $this->accessLevel == FieldClubUser::$ACCESSLEVEL_USER)
		{
			return $_SESSION['acceptedTAndC'];
		}
		else
		{
			return TRUE;
		}
	}

	function logIn($username,$userId)
	{
		$_SESSION['username'] = $username;
		$_SESSION['userId'] = $userId;
		$_SESSION['acceptedTAndC'] = FALSE;
		$_SESSION['lastRequest'] = time();
		$this->log("User logged in.");
		return TRUE;
	}

	public function logOut()
	{
		$this->log("User logged out.");
		unset($_SESSION['username']);
		unset($_SESSION['userId']);
		unset($_SESSION['goto']);
		unset($_SESSION['showCourt']);
		unset($_SESSION['acceptedTAndC']);
		return TRUE;
	}

    /**
      This function allows admin users to change to a user account of their chosing
      */
    public function changeUser($targetUserId)
    {
        if ($this->isAdmin())
        {
            $fieldclubUser = new FieldClubUser($this->core, $targetUserId);
            $_SESSION['username'] = $fieldclubUser->username;
            $_SESSION['userId'] = $targetUserId;
            Toolkit::redirect("?page=userPage"); 
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

	public function accountDisabled()
	{
		if ($this->userStatus == FieldClubUser::$USERSTATUS_RED)
		{
			trigger_error("Your account has been disabled. Contact Field Club admin.");
			$this->logOut();
			return TRUE;
		}
	}

	public function isLoggedIn()
	{
		if (isset($_SESSION['username']) && isset($_SESSION['userId']))
		{
			if (isset($_SESSION['lastRequest']) && (time() - $_SESSION['lastRequest'] < $this->core->getSessionExpireLimit()))
			{
				$_SESSION['lastRequest'] = time();
				
				
				return true;
			}
			else
			{
				$this->core->message("Your session has expired, please log in again.");
				$_SESSION['savedMessage'] = "Your session has expired, please log in again.";
				unset($_SESSION['username']);
				unset($_SESSION['userId']);
				unset($_SESSION['goto']);
				return false;
			}
		}
		return false;
		
	}
	
	public function showSavedMessages()
	{
		if (isset($_SESSION['savedMessage']))
		{
			$this->core->message($_SESSION['savedMessage']);
			unset($_SESSION['savedMessage']);
		}
	}
}

class FieldClubKitOrderItem extends FieldClub
{
	private $id = NULL;
	public $name = NULL;
	public $sizes = array();
	public $price = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getKitOrderItemDB("id=$id");
			if (isset($res) && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Kit order item $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->name = $data['name'];
			$this->sizes = ($data['sizes'] != NULL ? split(",",$data['sizes']) : "");
			$this->price = $data['price'];
		}
	}

	public function __toString()
	{
		return $this->name;
	}

	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function isDummyEntry()
	{
		return $this->id < 0;
	}

	public function getId()
	{
		return $this->id;
	}

	public function save()
	{
		trigger_error("save/update kit order item not implemented.");
		return FALSE;
	}

	public function delete()
	{
		if (!$this->isDummyEntry())
		{
			trigger_error("delete kit order size not implemented.");
		}
		else
		{
			return FALSE;
		}
	}
}

class FieldClubKitOrderSize extends FieldClub
{
	private $id = NULL;
	public $name = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getKitOrderSizeDB("id=$id");
			if ($res && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Kit order size $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->name = $data['name'];
		}
	}

	public function __toString()
	{
		return $this->name;
	}

	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function isDummyEntry()
	{
		return $this->id < 0;
	}

	public function getId()
	{
		return $this->id;
	}

	public function save()
	{
		trigger_error("save/update kit order price not implemented.");
		return FALSE;
	}

	public function delete()
	{
		if (!$this->isDummyEntry())
		{
			trigger_error("delete kit order price not implemented.");
		}
		else
		{
			return FALSE;
		}
	}
}

class FieldClubPlacedKitOrder extends FieldClub
{

	public static $STATUS_AWAITING_PAYMENT = 0;
	public static $STATUS_PAYMENT_RECEIVED = 1;
	public static $STATUS_ORDERED = 2;
	public static $STATUS_READY_FOR_COLLECTION = 3;
	public static $STATUS__MAP = array(0 => "Awaiting payment", 1 => "Payment received", 2 => "Ordered", 3 => "Ready for collection");

	private $id = NULL;
	public $userId = NULL;
	public $kitOrderId = NULL;
	public $orderedItems = array();
	public $status = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getPlacedKitOrderDB("id=$id");
			if (isset($res) && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Placed Kit order $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->userId = $data['userId'];
			$this->kitOrderId = $data['kitOrderId'];
			if ($data['orderedItems'] != NULL) {
				foreach (split(",",$data['orderedItems']) as $item) {
				list($itemId, $sizeId) = split(":", $item);
				$this->orderedItems[$itemId] = $sizeId;
				}
			}
			else {
				$this->orderedItems = array();
			}
			$this->status = $data['status'];
		}
	}

	public function __toString()
	{
		return $this->name;
	}

	public function getStatus() {
		return FieldClubPlacedKitOrder::$STATUS__MAP[$this->status];
	}

	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function isDummyEntry()
	{
		return $this->id < 0;
	}

	public function getId()
	{
		return $this->id;
	}

	public function save()
	{
		if (!$this->isDummyEntry())
		{
			$items = array();
			foreach ($this->orderedItems as $itemId => $sizeId) {
				$items[] = "{$itemId}:{$sizeId}";
			}
			if (!$this->loaded())
			{
				return $this->addPlacedKitOrderDB($this->userId,$this->kitOrderId,join(",",$items),$this->status);
			}
			else
			{
				return $this->updatePlacedKitOrderDB($this->id,$this->userId,$this->kitOrderId,join(",",$items),$this->status);
			}
		}
		else
		{
			return FALSE;
		}
	}

	public function delete()
	{
		if (!$this->isDummyEntry())
		{
			return $this->deletePlacedKitOrderDB($this->id);
		}
		else
		{
			return FALSE;
		}
	}
}

class FieldClubKitOrder extends FieldClub
{
	private $id = NULL;
	public $closingDate = NULL;
	public $paymentTo = NULL;
	public $chequeTo = NULL;
	public $adminUserId = NULL;
	public $name = NULL;
	public $items = NULL;
	
	public function __construct(Core $core, $id = NULL, $data = NULL)
	{
		parent::__construct($core);

		if ($id != NULL)
		{
			$res =& $this->getKitOrderDB("id=$id");
			if (isset($res) && $res->numRows() != 0)
			{
				$data = $res->fetchRow();
			}
			else
			{
				trigger_error("Kit order $id doesn't exist.", E_USER_ERROR);
			}
		}

		if ($data != NULL)
		{
			$this->id = $data['id'];
			$this->name = $data['name'];
			$this->closingDate = new T_DateTime($data['closingDate']);
			$this->items = ($data['items'] != NULL ? split(",",$data['items']) : array());
			$this->paymentTo = $data['paymentTo'];
			$this->adminUserId = $data['adminUserId'];
		}
	}

	public function getAdmin() {
		return new FieldClubUser($this->core, $this->adminUserId);
	}

	public function __toString()
	{
		return $this->name;
	}

	public function loaded()
	{
		return ($this->id != NULL);
	}       

	public function isDummyEntry()
	{
		return $this->id < 0;
	}

	public function getId()
	{
		return $this->id;
	}

	public function save()
	{
		trigger_error("updateKitOrderDB() not implemented.");
		return FALSE;
	}

	public function delete()
	{
		if (!$this->isDummyEntry())
		{
			return $this->deleteKitOrderDB($this->id);
		}
		else
		{
			return FALSE;
		}
	}
}


?>
