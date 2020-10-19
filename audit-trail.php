<?php 

//////////////////////////////////////////////////////////////////////////////////
	//	AUDIT TRAIL
/////////////////////////////////////////////////////////////////////////////////

// CLASS AND FUNCTIONS
class Crud
{
		private $conn;

		public function __construct($connectionObject)
		{
			$this->conn = $connectionObject;
		}

		public function LogAllCalculate($identifier, $userid, $fullname)
		{
			try 
			{	 
				$check = "SELECT * FROM general_log WHERE IPAddr = '".$_SERVER["REMOTE_ADDR"]."' 
										&& UserID = '".$userid."'";
				$q = $this->conn->prepare($check);
				$q->execute();
				//$q->fetch(PDO::FETCH_ASSOC);

				if($q->rowCount() < 1)
				{
					$sqlx = "INSERT INTO general_log SET Identifier = :identifier, 
												UserID = :userid, 
												FullName = :fullname, 
												Visit = :visit, 
												IPAddr = :ip_addr";
					$q = $this->conn->prepare($sqlx);
					$q->execute(array(':identifier'=>$identifier,
									':userid'=>$userid,
									':fullname'=>$fullname,
									':visit'=> 1,
									'ip_addr'=> $_SERVER["REMOTE_ADDR"]
								));
				}
				else
				{	
					$m = $q->fetch(PDO::FETCH_ASSOC);
					$visit = ($m["Visit"] + 1);

					$sqlz = "UPDATE general_log SET Visit = :visit, 
													Identifier = :identifier, 
													FullName = :fullname 
							WHERE IPAddr = '".$_SERVER["REMOTE_ADDR"]."' 
							&& UserID = '".$userid."'";

					$q = $this->conn->prepare($sqlz);
					$q->execute(array(':identifier'=>$identifier,
									':visit'=>$visit,
									':fullname'=>$fullname
									));
				}
			} 

			catch (PDOException $e) 
			{
				echo $e->getMessage();
			}
			
		}


		public function LogAudit($identifier, $userid, $fullname)
		{
			$Timenow = date("Y-m-d G:i:s");
			try 
			{	
				$url = $_SERVER["REQUEST_URI"];

				$sqlx = "INSERT INTO audit_log SET Task = :task, 
											UserID = :userid, 
											FullName = :fullname,
											Identifier = :identifier, 
											DateModified = :datez,
											IPAddr = :ip_addr";
				$q = $this->conn->prepare($sqlx);
				$q->execute(array(':task'=>$url,
								':userid'=>$userid,
								':fullname'=>$fullname,
								':identifier'=>$identifier,
								':datez'=>date("Y-m-d G:i:s"),
								'ip_addr'=> $_SERVER["REMOTE_ADDR"]
							));
			} 

			catch (PDOException $e) 
			{
				echo $e->getMessage();
			}
			
		}
}




//CONTROLS
function FetchTableContent($i)
{

	global $connect;
	$crud = new Crud($connect);

	switch ($i) {

		//select total unique users from general_log table
		case 10:
			$data = $crud->select("general_log","COUNT(ID) as Total", "", "ID DESC");
			return $data[0]["Total"];
			break;

		//select total clicks from general_log table
		case 11:
			$data = $crud->select("general_log","SUM(Visit) as Total", "", "ID DESC");
			return $data[0]["Total"];
			break;

		//select total unique user clicks from general_log table(For Individual Dashboards)
		case 12:
			$data = $crud->select("general_log","COUNT(Visit) as Total", "", "ID DESC");
			return $data[0]["Total"];

		//select all row from Audit Log
		case 13:
			$data = $crud->select("audit_log","*", "", "COUNT(ID) DESC", "UserID");
			return $data;
			break;

		//select total unique users from general_log table
		case 14:
			$data = $crud->select("users","COUNT(ID) as Total", "", "ID DESC");
			return $data[0]["Total"];
			break;

	}
}



//PUT THIS AT THE TOP OF EVERY PAGE YOU WANT TO READ
$home = new Crud($connect);

if(isset($_SESSION["duid"])) {
	$sname = $home->AnyContent("Sname", "users", "ID", $_SESSION["duid"]);
	$fname = $home->AnyContent("Fname", "users", "ID", $_SESSION["duid"]);
}

$user_id = (isset($_SESSION["duid"])) ? $_SESSION["duid"] : 0;

$identifier = (isset($_SESSION["duid"])) ? "2" : "1";
$userid = (isset($user_id)) ? $user_id : "NULL";
$fname = $home -> AnyContent("FName", "users", "ID", $user_id);
$sname = $home -> AnyContent("SName", "users", "ID", $user_id);
$fullname = (isset($user_id)) ? $fname." ".$sname : "NULL";

//$forgo -> LogAll($identifier, $userid, $fullname);
$home -> LogAllCalculate($identifier, $userid, $fullname);
$home -> LogAudit($identifier, $userid, $fullname);



//PUT THIS IN THE ADMIN DASHBOARD TO READ AND UPDATE AUDIT TRAIL
//Total Clicks
 echo FetchTableContent(11); 
//Active Users
 echo FetchTableContent(10);
 //Registered Users
 echo FetchTableContent(14);
 //Unique Clicks
 echo FetchTableContent(12);
                



?>