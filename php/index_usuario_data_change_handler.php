<?php

    $session_handler_include_origin = "back_end";
    $session_handler_permission_code = 2;
    include_once "session_handler.php";

    $json_response = Array();

    try {

        if (isset($_POST['jsondata'])) {
            $json_data = json_decode($_POST['jsondata']);
        } else {
            $json_data = json_decode(file_get_contents('php://input'));
        }

        ##$pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "id12782411_ferreteria_root_db_user", 'db_pass' => 'EZQW4XB$6n2hf8%', 'db_name' => "id12782411_ferreteria"));
		$pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "root", 'db_pass' => '', 'db_name' => "id12782411_ferreteria"));

        switch ($json_data->changeHeader) {
            case "prueba-001-responseRegistry":

                $testId = $json_data->changeArguments->testId;
                $responseJson = json_encode($json_data->changeArguments->response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $query_args = array(
                    "testid" => $testId
                    ,"personid" => PERSON_ID
                );
                $query = "SELECT prueba_psicotecnica.id FROM prueba_psicotecnica INNER JOIN persona_prueba ON prueba_psicotecnica.id = persona_prueba.id_prueba WHERE identificador_prueba = :testid AND persona_prueba.id_persona = :personid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] > 0) {

                    $testId = $query_data[0][0]["id"];

                    $query_args = array(
                        "testid" => $testId
                        ,"personid" => PERSON_ID
                    );
                    $query = "SELECT id FROM persona_respuesta WHERE id_prueba = :testid AND id_persona = :personid";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");

                    if ($query_data[1] == 0) {
                        $query_args = array(
                            "testid" => $testId
                            , "personid" => PERSON_ID
                            , "testjson" => $responseJson
                        );
                        $query = "INSERT INTO persona_respuesta (id_prueba, id_persona, respuesta_json, fecha_respuesta) VALUES (:testid, :personid, :testjson, NOW())";
                        $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                        logHandler(array("query" => $query_data[2], "action" => "insert"));

                        $json_response["values"] = array(
                            "dataChangeCode" => 1
                        );
                    } else {
                        $json_response["values"] = array(
                            "dataChangeCode" => 2
                        );
                    }
                } else {
                    $json_response["values"] = array(
                        "dataChangeCode" => 0
                    );
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            default:
                throw new RuntimeException("Caught exception: PHP data change handler error, change header '".$json_data->changeHeader."' not found");
                break;
        }
    } catch (Exception $e) {
        $json_response["statusCode"] = 500;
        $json_response["errorMessage"] = $e->getMessage();
    }

    echo json_encode($json_response);

?>