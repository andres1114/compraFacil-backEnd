<?php

    include_once "db_connection.php";
    $json_response = Array();
    ob_start();

    try {

        if (isset($_POST['jsondata'])) {
            $json_data = json_decode($_POST['jsondata']);
        } else {
            $json_data = json_decode(file_get_contents('php://input'));
        }

        $pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "root", 'db_pass' => '', 'db_name' => "compraFacil"));

        switch ($json_data->callHeader) {
            case "login":

                $query_args = array(
                    "username" => $json_data->callArguments->userName
                    ,"userpassword" => $json_data->callArguments->password
                );
                $query = "SELECT usuario.id AS userid,pagina_redireccion FROM usuario INNER JOIN tipo_usuario ON usuario.id_tipo_usuario = tipo_usuario.id WHERE nombre_usuario = :username AND contrasena_md5 = :userpassword AND activo IS TRUE LIMIT 0,1";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "login_code" => 0
                    ,"session_id" => ""
                    ,"redirect_page" => ""
                );

                if ($query_data[1] > 0) {
                    $checksum_id = md5(uniqid(rand()));
                    $nonce_value = md5(uniqid(rand()));

                    $query_args = array(
                        "userid" => $query_data[0][0]["userid"]
                    );
                    $query = "DELETE FROM session WHERE user_id = :userid";
                    pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_02");

                    $query_args = array(
                        "userid" => $query_data[0][0]["userid"]
                        ,"bluecvid" => $checksum_id
                        ,"noncevalue" => $nonce_value
                    );
                    $query = "INSERT INTO session (user_id, session_id, nonce_value, creation_date, expiration_date) VALUES (:userid, :bluecvid, :noncevalue, NOW(), DATE_ADD(NOW(), INTERVAL 31 DAY))";
                    pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_02");

                    $json_response["values"]["login_code"] = 200;
                    $json_response["values"]["session_id"] = $checksum_id;
                    $json_response["values"]["redirect_page"] = $query_data[0][0]["pagina_redireccion"];

                } else {
                    $json_response["values"]["login_code"] = 404;
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "cookieCheck":

                $query_args = array(
                    "cookieid" => $json_data->callArguments->cookieId
                );
                $query = "SELECT session.id AS sessionid,pagina_redireccion,expiration_date FROM session INNER JOIN usuario ON session.user_id = usuario.id INNER JOIN tipo_usuario ON usuario.id_tipo_usuario = tipo_usuario.id WHERE session_id = :cookieid AND activo IS TRUE LIMIT 0,1";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                if ($query_data[1] > 0) {
                    $cookie_expiration_date = new DateTime($query_data[0][0]["expiration_date"], new DateTimeZone("America/Bogota"));
                    $server_current_time = new DateTime(null, new DateTimeZone("America/Bogota"));

                    if ($cookie_expiration_date > $server_current_time) {
                        $query_args = array(
                            "sessionid" => $query_data[0][0]["sessionid"]
                        );
                        $query = "UPDATE session SET expiration_date = DATE_ADD(NOW(), INTERVAL 31 DAY) WHERE id = :sessionid";
                        //pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_02");

                        $json_response["values"]["login_code"] = 200;
                        $json_response["values"]["redirect_page"] = $query_data[0][0]["pagina_redireccion"];
                    } else {
                        $query_args = array(
                            "sessionid" => $query_data[0][0]["sessionid"]
                        );
                        $query = "DELETE FROM session WHERE id = :sessionid";
                        pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_03");

                        $json_response["values"]["login_code"] = 303;
                    }
                } else {
                    $json_response["values"]["login_code"] = 404;
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            default:
                throw new RuntimeException("Caught exception: PHP login handler error, login header '".$json_data->callHeader."' not found");
                break;
        }

    } catch (Exception $e) {
        $json_response["statusCode"] = 500;
        $json_response["errorMessage"] = $e->getMessage();
    }

echo json_encode($json_response);

?>