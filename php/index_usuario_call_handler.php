<?php

    $session_handler_include_origin = "back_end";
    $session_handler_permission_code = 2;
    include_once "session_handler.php";

    $json_response = Array();

    ob_start();

    try {

        if (isset($_POST['jsondata'])) {
            $json_data = json_decode($_POST['jsondata']);
        } else {
            $json_data = json_decode(file_get_contents('php://input'));
        }

		$pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "root", 'db_pass' => 'admin', 'db_name' => "compraFacil"));

        switch ($json_data->callHeader) {
            case "closeSession":

                $query_args = array(
                    "cookieid" => $json_data->callArguments->cookieId
                );
                $query = "DELETE FROM session WHERE session_id = :cookieid";
                pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            default:
                throw new RuntimeException("Caught exception: PHP call handler error, call header '".$json_data->callHeader."' not found");
                break;
        }
    } catch (Exception $e) {
        $json_response["statusCode"] = 500;
        $json_response["errorMessage"] = $e->getMessage();
    }

    echo json_encode($json_response);

?>