<?php
	
	date_default_timezone_set("America/Bogota");
	
    $json_response = Array(
        "statusCode" => 0
        ,"errorMessage" => ""
    );
	
	include_once "db_connection.php";
	
    if (isset($_COOKIE['session_id'])) {

		$pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "root", 'db_pass' => '', 'db_name' => "compraFacil"));
        $query_args = array(
            "cookieid" => $_COOKIE['session_id']
        );
        $query = "SELECT usuario.id AS userid, nombre_usuario, valor_tipo_usuario, nonce_value FROM session INNER JOIN usuario ON session.user_id = usuario.id INNER JOIN tipo_usuario ON usuario.id_tipo_usuario = tipo_usuario.id WHERE session_id = :cookieid AND activo IS TRUE LIMIT 0,1";
        $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,"verify_user_query_01");

        if ($query_data[1] > 0) {
            $permission_flag = false;
            for ($x = 0; $x < sizeof($session_handler_permission_code); $x++) {
                if ($session_handler_permission_code[$x] == $query_data[0][0]["valor_tipo_usuario"] || $query_data[0][0]["valor_tipo_usuario"] == 1) {
                    //Allow the script to be executed

                    $server_time = new DateTime(null, new DateTimeZone("America/Bogota"));

                    $md5_string = "d079df43f0c5f2084060c89b4bc9d56a";
                    $divided_string = str_split($md5_string, 8);
                    $new_md5_string = $divided_string[2].$divided_string[1].$divided_string[3].$divided_string[0];

                    define("USER_ID",$query_data[0][0]["userid"]);
                    define("USER_NAME",$query_data[0][0]["nombre_usuario"]);
                    define("SERVER_TIME",$server_time->format('Y-m-d H:i:s'));
                    define("NONCE_VALUE",$query_data[0][0]["nonce_value"]);
                    define("BLOWFISH_VALUE", $new_md5_string);

			    	$query_args = array(
			    		"userid" => USER_ID
			    	);
			    	$query = "SELECT id_empleado, CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre_completo FROM empleado INNER JOIN empleado_usuario ON empleado.id = empleado_usuario.id_empleado WHERE empleado_usuario.id_usuario = :userid";
			    	$query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,"verify_user_query_02");

			    	if ($query_data[1] > 0) {
			    		define("PERSON_NAME",$query_data[0][0]["nombre_completo"]);
			    		define("PERSON_ID",$query_data[0][0]["id_empleado"]);
			    	} else {
			    		define("PERSON_NAME","");
			    		define("PERSON_ID","");
			    	}

                    $permission_flag = true;
			    	break;
                }
            }
            if ($permission_flag == false) {
                //Return the user to a 403 site
                switch($session_handler_include_origin) {
                    case "front_end":
                        //header("location: 403.php");
                        js_redirect(array("redirect_url" => "403.php", "redirect_delay_seconds" => 0));
                        exit;
                        break;
                    case "back_end":
                        $json_response["statusCode"] = 403;
                        $json_response["errorMessage"] = "ERR_USER_NOT_ALLOWED The current user logged in has no permission to use this website";
                        echo json_encode($json_response);
                        exit;

                        break;
                }
            }
        } else {
            //Return the user to a 401 site
            switch($session_handler_include_origin) {
                case "front_end":
                    //header("location: 401.php");
					js_redirect(array("redirect_url" => "401.php", "redirect_delay_seconds" => 0));
                    exit;
                    break;
                case "back_end":
                    $json_response["statusCode"] = 500;
                    $json_response["errorMessage"] = "ERR_USER_NOT_FOUND The current user logged in was not found in the database";
                    echo json_encode($json_response);
                    exit;

                    break;
            }
        }
    } else {
        //Return the user to a 403 site
        switch($session_handler_include_origin) {
            case "front_end":
                //header("location: login.php?returnUrl=" . "http" . (!empty($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
				js_redirect(array("redirect_url" => "login.php?returnUrl=" . "http" . (!empty($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], "redirect_delay_seconds" => 0));
                exit;
                break;
            case "back_end":
                $json_response["statusCode"] = 401;
                $json_response["errorMessage"] = "ERR_NO_USER_COOKIE_SET The bluecv's cookie has not been set";
                echo json_encode($json_response);
                exit;

                break;
        }
    }

    unset($json_response);
    unset($pdo_mysql_rw);

?>