<?php
/**
  This script serves a generic proxy to get objects from the database, it
  will be replaced once a better object representation is in place.

  At the moment each property must be explicitly be added.

  */

if (!isset($_GET['type']) || !isset($_GET['id'])) {
	die();
}

chdir("../");
require_once("inc/core.class.php");
$core = new Core();
$fieldClub = new FieldClub($core);


$type = $_GET['type'];
$id = $_GET['id'];

switch ($type)
{
    case "court": 
    {
        $fieldClubCourt = new FieldClubCourt($core,$id);

        if ($fieldClubCourt->loaded())
        {

            $sports = array();
            foreach ($fieldClubCourt->usedBySports as $sportId) {
                $sport = new FieldClubSport($core, $sportId);
                $sports[] = array("id" => $sport->getId(), "name" => "$sport");
            }

            $data = array("sports" => $sports);
            echo json_encode($data);
        }
    }
    break;

    case "team":
    {
        $fieldClubTeam = new FieldClubTeam($core, $id);
        $data = array("sportId" => $fieldClubTeam->sportId);
        echo json_encode($data);
    }
    break;

    default:
    {
        // Fail silently if we haven't defined an accessor for this object type
    }
    break;

	case "all_sports":
	{

	}
}

?>
