<?php

    $session_handler_include_origin = "back_end";
    $session_handler_permission_code = array(1,2);
    include_once "session_handler.php";

    $json_response = Array();

    try {

        if (isset($_POST['jsondata'])) {
            $json_data = json_decode($_POST['jsondata']);
        } else {
            $json_data = json_decode(file_get_contents('php://input'));
        }

		$pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "root", 'db_pass' => "admin", 'db_name' => "compraFacil"));

        switch ($json_data->changeHeader) {
            case "personRegistry":

                $personId = $json_data->changeArguments->personId;
                $personIdType = 1;
                $personFirstName = $json_data->changeArguments->personFirstName;
                $personLastName = $json_data->changeArguments->personLastName;
                $addUser = $json_data->changeArguments->addUser;

                if ($addUser == 1) {
                    $addUserFlag = true;
                } else {
                    $addUserFlag = false;
                }

                if ($addUserFlag) {
                    include_once "php_js_encryption.php";
                    $Encryption = new Encryption();

                    $userName = $json_data->changeArguments->userName;
                    $userPassword = $Encryption->decrypt($json_data->changeArguments->userPassword,NONCE_VALUE);
                } else {
                    $userName = NULL;
                    $userPassword = NULL;
                }

                $query_args = array(
                    "personid" => $personId
                );
                $query = "SELECT id FROM empleado WHERE numero_identificacion_empleado = :personid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] == 0) {
                    $query_args = array(
                        "personidtype" => $personIdType
                        ,"personid" => $personId
                        , "personfirstname" => $personFirstName
                        , "personlastname" => $personLastName
                    );
                    $query = "INSERT INTO empleado (id_tipo_identificacion, numero_identificacion_empleado, nombre_empleado, apellido_empleado, fecha_registro_empleado, activo) VALUES (:personidtype, :personid, :personfirstname, :personlastname, NOW(), TRUE)";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");
                    logHandler(array("query" => $query_data[2], "action" => "insert"));

                    $person_id = $query_data[3];

                    if ($query_data[3] > 0) {

                        $json_response["values"] = array(
                            "dataChangeCode" => 1
                            , "personId" => $person_id
                        );

                        if ($addUserFlag) {

                            $query_args = array(
                                "username" => $userName
                            );
                            $query = "SELECT id FROM usuario WHERE nombre_usuario = :username";
                            $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");

                            if ($query_data[1] == 0) {
                                $query_args = array(
                                    "username" => $userName
                                    , "userpassword_md5" => MD5($userPassword)
                                    , "userpassword_sha256" => encryptControl("encrypt", $userPassword, BLOWFISH_VALUE)
                                    , "usertype" => 2
                                );
                                $query = "INSERT INTO usuario (nombre_usuario, contrasena_md5, contrasena_sha256, id_tipo_usuario, activo) VALUES (:username, :userpassword_md5, :userpassword_sha256, :usertype, TRUE)";
                                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_04");
                                logHandler(array("query" => $query_data[2], "action" => "insert"));

                                if ($query_data[3] > 0) {
                                    $user_id = $query_data[3];

                                    $query_args = array(
                                        "userid" => $user_id
                                        , "personid" => $person_id
                                    );
                                    $query = "INSERT INTO empleado_usuario (id_usuario, id_empleado) VALUES (:userid, :personid)";
                                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_05");
                                    logHandler(array("query" => $query_data[2], "action" => "insert"));

                                } else {
                                    throw new RuntimeException("Caught exception: There was an error during execution of '" . $json_data->changeHeader . "' as the tema's INSERT query returned no new tema_id");
                                }

                            } else {
                                $json_response["values"] = array(
                                    "dataChangeCode" => 2
                                    , "personId" => $person_id
                                );
                            }
                        }

                    } else {
                        throw new RuntimeException("Caught exception: There was an error during execution of '" . $json_data->changeHeader . "' as the tema's INSERT query returned no new tema_id");
                    }
                } else {
                    $json_response["values"] = array(
                        "dataChangeCode" => 0
                    );
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "personUpdate":

                $personId = $json_data->changeArguments->personId;
                $valueToUpdate = $json_data->changeArguments->valueToUpdate;
                $fieldToUpdate = $json_data->changeArguments->fieldToUpdate;

                for ($x = 0; $x < sizeof($valueToUpdate); $x++) {
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                            $query = "UPDATE empleado SET numero_identificacion_empleado = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 2:
                            $query = "UPDATE empleado SET nombre_empleado = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 3:
                            $query = "UPDATE empleado SET apellido_empleado = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 7:
                            $query = "UPDATE usuario INNER JOIN empleado_usuario ON usuario.id = empleado_usuario.id_usuario SET nombre_usuario = :toupdatevalue WHERE empleado_usuario.id_empleado = :toupdateid";
                            $logAction = "update";
                            break;
                        case 8:
                            $query = "UPDATE usuario INNER JOIN empleado_usuario ON usuario.id = empleado_usuario.id_usuario SET contrasena_md5 = :toupdatevalue_1, contrasena_sha256 = :toupdatevalue_2 WHERE empleado_usuario.id_empleado = :toupdateid";
                            $logAction = "update";
                            break;
                        case 9:
                            $query = "UPDATE usuario INNER JOIN empleado_usuario ON usuario.id = empleado_usuario.id_usuario SET activo = :toupdatevalue WHERE empleado_usuario.id_empleado = :toupdateid";
                            $logAction = "update";
                            break;
                        case 10:
                            $query = "DELETE FROM usuario WHERE id = (SELECT empleado_usuario.id_usuario FROM empleado_usuario WHERE id_empleado = :toupdateid)";
                            $logAction = "delete";
                            break;
                        case 11:
                            $query = "UPDATE empleado SET activo = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 12:
                            $query = array(
                                "DELETE FROM usuario WHERE id = (SELECT empleado_usuario.id_usuario FROM empleado_usuario WHERE id_empleado = :toupdateid)"
                                ,"DELETE FROM empleado WHERE id = :toupdateid"
                            );
                            $logAction = "delete";
                            break;
                    }
                    switch ($valueToUpdate[$x]) {
                        case "true":
                            $valueToUpdate[$x] = 1;
                            break;
                        case "false":
                            $valueToUpdate[$x] = 0;
                            break;
                    }
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 9:
                        case 11:
                            $query_args = array(
                                "toupdateid" => $personId
                                , "toupdatevalue" => $valueToUpdate[$x]
                            );
                            break;
                        case 8:

                            include_once "php_js_encryption.php";
                            $Encryption = new Encryption();

                            $userPassword = $Encryption->decrypt($json_data->changeArguments->userPassword,NONCE_VALUE);

                            $query_args = array(
                                "toupdateid" => $personId
                                , "toupdatevalue_1" => MD5($userPassword)
                                , "toupdatevalue_2" => encryptControl("encrypt", $userPassword, BLOWFISH_VALUE)
                            );
                            break;
                        case 10:
                        case 12:
                            $query_args = array(
                                "toupdateid" => $personId
                            );
                            break;
                    }

                    if (is_array($query)) {
                        for ($y = 0; $y < sizeof($query); $y++) {
                            $query_data = pdoExecuteQuery($pdo_mysql_rw, $query[$y], $query_args, $json_data->changeHeader . "_query_03");
                            logHandler(array("query" => $query_data[2], "action" => $logAction));
                        }
                    } else {
                        $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                        logHandler(array("query" => $query_data[2], "action" => $logAction));
                    }

                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "userRegistry":

                include_once "php_js_encryption.php";
                $Encryption = new Encryption();

                $person_id = $json_data->changeArguments->personId;
                $userType = $json_data->changeArguments->userType;
                $userName = $json_data->changeArguments->userName;
                $userPassword = $Encryption->decrypt($json_data->changeArguments->userPassword,NONCE_VALUE);

                $query_args = array(
                    "username" => $userName
                );
                $query = "SELECT id FROM usuario WHERE nombre_usuario = :username";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] == 0) {
                    $query_args = array(
                        "username" => $userName
                        , "userpassword_md5" => MD5($userPassword)
                        , "userpassword_sha256" => encryptControl("encrypt", $userPassword, BLOWFISH_VALUE)
                        , "usertype" => $userType
                    );
                    $query = "INSERT INTO usuario (nombre_usuario, contrasena_md5, contrasena_sha256, id_tipo_usuario, activo) VALUES (:username, :userpassword_md5, :userpassword_sha256, :usertype, TRUE)";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_04");
                    logHandler(array("query" => $query_data[2], "action" => "insert"));

                    if ($query_data[3] > 0) {
                        if ($person_id != 0) {
                            $user_id = $query_data[3];

                            $query_args = array(
                                "userid" => $user_id
                                , "personid" => $person_id
                            );
                            $query = "INSERT INTO empleado_usuario (id_usuario, id_empleado) VALUES (:userid, :personid)";
                            $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_05");
                            logHandler(array("query" => $query_data[2], "action" => "insert"));
                        }

                    } else {
                        throw new RuntimeException("Caught exception: There was an error during execution of '" . $json_data->changeHeader . "' as the user's INSERT query returned no new id_usuario");
                    }

                    $json_response["values"] = array(
                        "dataChangeCode" => 1
                    );

                } else {
                    $json_response["values"] = array(
                        "dataChangeCode" => 0
                    );
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "userUpdate":

                $userId = $json_data->changeArguments->userId;
                $valueToUpdate = $json_data->changeArguments->valueToUpdate;
                $fieldToUpdate = $json_data->changeArguments->fieldToUpdate;

                for ($x = 0; $x < sizeof($valueToUpdate); $x++) {
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                            $query = "UPDATE usuario SET id_tipo_usuario = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 2:
                            $query = "UPDATE usuario SET nombre_usuario = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 3:
                            $query = "UPDATE usuario SET contrasena_md5 = :toupdatevalue_1, contrasena_sha256 = :toupdatevalue_2 WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 4:
                            $query = "UPDATE usuario SET activo = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 5:
                            $query = array(
                                "DELETE FROM usuario WHERE id = :toupdateid"
                            );
                            $logAction = "delete";
                            break;
                    }
                    switch ($valueToUpdate[$x]) {
                        case "true":
                            $valueToUpdate[$x] = 1;
                            break;
                        case "false":
                            $valueToUpdate[$x] = 0;
                            break;
                    }
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                        case 2:
                        case 4:
                            $query_args = array(
                                "toupdateid" => $userId
                                , "toupdatevalue" => $valueToUpdate[$x]
                            );
                            break;
                        case 3:

                            include_once "php_js_encryption.php";
                            $Encryption = new Encryption();

                            $userPassword = $Encryption->decrypt($valueToUpdate[$x],NONCE_VALUE);

                            $query_args = array(
                                "toupdateid" => $userId
                                , "toupdatevalue_1" => MD5($userPassword)
                                , "toupdatevalue_2" => encryptControl("encrypt", $userPassword, BLOWFISH_VALUE)
                            );
                            break;
                        case 5:
                            $query_args = array(
                                "toupdateid" => $userId
                            );
                            break;
                    }

                    if (is_array($query)) {
                        for ($y = 0; $y < sizeof($query); $y++) {
                            $query_data = pdoExecuteQuery($pdo_mysql_rw, $query[$y], $query_args, $json_data->changeHeader . "_query_03");
                            logHandler(array("query" => $query_data[2], "action" => $logAction));
                        }
                    } else {
                        $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                        logHandler(array("query" => $query_data[2], "action" => $logAction));
                    }

                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "groceryRegistry":
                $maxFileSize = 1.5e+7;
                $uploadDirectory = 'imagenes/';

                $groceryName = $json_data->changeArguments->groceryName;
                $groceryWebsite = $json_data->changeArguments->groceryWebsite;
                if (isset($_FILES['filedata'])) {
                    $imageFile = ($_FILES['filedata'] != "null" ? $_FILES['filedata'] : null);
                } else {
                    $imageFile = null;
                }

                $query_args = array(
                    "groceryname" => $groceryName
                );
                $query = "SELECT id FROM almacen WHERE nombre_almacen = :groceryname";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] == 0) {
                    if ($imageFile != null) {
                        $insertImageFlag = true;
                        if (!isset($imageFile) && $imageFile["error"] != UPLOAD_ERR_OK) {
                            //There was an upload error
                            $json_response["values"] = array(
                                "dataChangeCode" => 2
                            );
                            $insertImageFlag = false;
                        }
                        if ($imageFile["size"] > $maxFileSize) {
                            //The given filesize is greater than 15mb
                            $json_response["values"] = array(
                                "dataChangeCode" => 3
                            );
                            $insertImageFlag = false;
                        }
                        if (sizeof(explode(".",clean($imageFile['name']))) > 2) {
                            //The filename contains more than one dot
                            $json_response["values"] = array(
                                "dataChangeCode" => 4
                            );
                            $insertImageFlag = false;
                        }
                        $file_Name = explode(".",clean($imageFile['name']));
                        $file_Name = clean($file_Name[0]);
                        $file_Ext = explode(".",clean($imageFile['name']));
                        $file_Ext = strtolower($file_Ext[1]);
                    } else {
                        $insertImageFlag = false;
                    }

                    if ($insertImageFlag) {
                        $indexName = md5(uniqid(rand(), true));
                        $query_args = array(
                            "filename" => $file_Name
                            ,"fileextension" => $file_Ext
                            ,"fileindex" => $indexName
                        );
                        $query = "INSERT INTO imagen (nombre_archivo,extencion_archivo,index_archivo) VALUES (:filename, :fileextension, :fileindex)";
                        $query_data_2 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");
                        logHandler(array("query" => $query_data_2[2], "action" => "insert"));

                        $newFileName = $indexName.".".$file_Ext;

                        if(!move_uploaded_file($imageFile['tmp_name'], $uploadDirectory.$newFileName)) {
                            //The given file was not able to get stored in the upload directory
                            $json_response["values"] = array(
                                "dataChangeCode" => 5
                            );
                        }
                    }
                    if ($insertImageFlag) {
                        $query_args = array(
                            "groeryname" => $groceryName
                            ,"grocerywebsite" => $groceryWebsite
                            ,"imageid" => $query_data_2[3]
                            ,"active" => TRUE
                        );
                    } else {
                        $query_args = array(
                            "groeryname" => $groceryName
                            ,"grocerywebsite" => $groceryWebsite
                            ,"imageid" => NULL
                            ,"active" => TRUE
                        );
                    }

                    $query = "INSERT INTO almacen (nombre_almacen, pagina_web_almacen, id_imagen, activo) VALUES (:groeryname, :grocerywebsite, :imageid, :active)";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                    logHandler(array("query" => $query_data[2], "action" => "insert"));

                    if (!isset($json_response["values"]["dataChangeCode"])) {
                        $json_response["values"] = array(
                            "dataChangeCode" => 1
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
            case "groceryUpdate":
                $maxFileSize = 1.5e+7;
                $uploadDirectory = 'imagenes/';

                $groceryId = $json_data->changeArguments->groceryId;
                $valueToUpdate = $json_data->changeArguments->valueToUpdate;
                $fieldToUpdate = $json_data->changeArguments->fieldToUpdate;
                if (isset($_FILES['filedata'])) {
                    $imageFile = ($_FILES['filedata'] != "null" ? $_FILES['filedata'] : null);
                } else {
                    $imageFile = null;
                }

                for ($x = 0; $x < sizeof($valueToUpdate); $x++) {
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                            $query = "UPDATE almacen SET nombre_almacen = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 2:
                            $query = "UPDATE almacen SET pagina_web_almacen = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 3:
                            $valueToUpdate[$x] = null;

                            if ($imageFile != null) {
                                $insertImageFlag = true;
                                if (!isset($imageFile) && $imageFile["error"] != UPLOAD_ERR_OK) {
                                    //There was an upload error
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 2
                                    );
                                    $insertImageFlag = false;
                                }
                                if ($imageFile["size"] > $maxFileSize) {
                                    //The given filesize is greater than 15mb
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 3
                                    );
                                    $insertImageFlag = false;
                                }
                                if (sizeof(explode(".",clean($imageFile['name']))) > 2) {
                                    //The filename contains more than one dot
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 4
                                    );
                                    $insertImageFlag = false;
                                }
                                $file_Name = explode(".",clean($imageFile['name']));
                                $file_Name = clean($file_Name[0]);
                                $file_Ext = explode(".",clean($imageFile['name']));
                                $file_Ext = strtolower($file_Ext[1]);
                            } else {
                                $insertImageFlag = false;
                            }

                            if ($insertImageFlag) {
                                $indexName = md5(uniqid(rand(), true));
                                $query_args = array(
                                    "filename" => $file_Name
                                    , "fileextension" => $file_Ext
                                    , "fileindex" => $indexName
                                );
                                $query = "INSERT INTO imagen (nombre_archivo,extencion_archivo,index_archivo) VALUES (:filename, :fileextension, :fileindex)";
                                $query_data_1 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");
                                logHandler(array("query" => $query_data_1[2], "action" => "insert"));

                                $newFileName = $indexName . "." . $file_Ext;

                                if (!move_uploaded_file($imageFile['tmp_name'], $uploadDirectory . $newFileName)) {
                                    //The given file was not able to get stored in the upload directory
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 5
                                    );
                                } else {
                                    $valueToUpdate[$x] = $query_data_1[3];

                                    $query_args = array(
                                        "id" => $groceryId
                                    );
                                    $query = "SELECT CONCAT(index_archivo,'.',extencion_archivo) AS imagen_almacen, imagen.id AS image_id FROM almacen LEFT JOIN imagen ON almacen.id_imagen = imagen.id WHERE almacen.id = :id";
                                    $query_data_2 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");
                                    $query_data_2[0][0]["imagen_almacen"];
                                    if ($query_data_2[0][0]["imagen_almacen"] != null) {
                                        try {
                                            unlink($uploadDirectory . $query_data_2[0][0]["imagen_almacen"]);
                                            $query_args = array(
                                                "imageid" => $query_data_2[0][0]["image_id"]
                                            );
                                            $query = "DELETE FROM imagen WHERE id = :imageid";
                                            $query_data_3 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                                            logHandler(array("query" => $query_data_3[2], "action" => "delete"));
                                        } catch (Exception $e) {
                                            $json_response["values"] = array(
                                                "dataChangeCode" => 6
                                            );
                                        }
                                    }
                                }
                            }
                            if ($valueToUpdate[$x] == null) {
                                $query_args = array(
                                    "id" => $groceryId
                                );
                                $query = "SELECT id_imagen FROM almacen WHERE id = :id";
                                $query_data_4 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_04");
                                $valueToUpdate[$x] = $query_data_4[0][0]["id_imagen"];
                            }

                            $query = "UPDATE almacen SET id_imagen = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 4:
                            $query = "UPDATE almacen SET activo = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 5:
                            $query_args = array(
                                "id" => $groceryId
                            );
                            $query = "SELECT CONCAT(index_archivo,'.',extencion_archivo) AS imagen_almacen, imagen.id AS image_id FROM almacen LEFT JOIN imagen ON almacen.id_imagen = imagen.id WHERE almacen.id = :id";
                            $query_data_5 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_05");
                            $query_data_5[0][0]["imagen_almacen"];
                            if ($query_data_5[0][0]["imagen_almacen"] != null) {
                                try {
                                    unlink($uploadDirectory . $query_data_5[0][0]["imagen_almacen"]);
                                    $query_args = array(
                                        "imageid" => $query_data_5[0][0]["image_id"]
                                    );
                                    $query = "DELETE FROM imagen WHERE id = :imageid";
                                    $query_data_6 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_06");
                                    logHandler(array("query" => $query_data_6[2], "action" => "delete"));
                                } catch (Exception $e) {
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 6
                                    );
                                }
                            }
                            $query = array(
                                "DELETE FROM almacen WHERE id = :toupdateid"
                            );
                            $logAction = "delete";
                            break;
                    }
                    switch ($valueToUpdate[$x]) {
                        case "true":
                            $valueToUpdate[$x] = 1;
                            break;
                        case "false":
                            $valueToUpdate[$x] = 0;
                            break;
                    }
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                            $query_args = array(
                                "toupdateid" => $groceryId
                                , "toupdatevalue" => $valueToUpdate[$x]
                            );
                            break;
                        case 5:
                            $query_args = array(
                                "toupdateid" => $groceryId
                            );
                            break;
                    }

                    if (is_array($query)) {
                        for ($y = 0; $y < sizeof($query); $y++) {
                            $query_data = pdoExecuteQuery($pdo_mysql_rw, $query[$y], $query_args, $json_data->changeHeader . "_query_07");
                            logHandler(array("query" => $query_data[2], "action" => $logAction));
                        }
                    } else {
                        $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_08");
                        logHandler(array("query" => $query_data[2], "action" => $logAction));
                    }
                }

                if (!isset($json_response["values"]["dataChangeCode"])) {
                    $json_response["values"] = array(
                        "dataChangeCode" => 1
                    );
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "productRegistry":

                $maxFileSize = 1.5e+7;
                $uploadDirectory = 'imagenes/';

                $groceryId = $json_data->changeArguments->groceryId;
                $productName = $json_data->changeArguments->productName;
                $productPrize = $json_data->changeArguments->productPrize;
                if (isset($_FILES['filedata'])) {
                    $imageFile = ($_FILES['filedata'] != "null" ? $_FILES['filedata'] : null);
                } else {
                    $imageFile = null;
                }

                $query_args = array(
                    "groceryid" => $groceryId
                    ,"productname" => $productName
                );
                $query = "SELECT id FROM producto WHERE nombre_producto = :productname AND id_almacen = :groceryid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] == 0) {
                    if ($imageFile != null) {
                        $insertImageFlag = true;
                        if (!isset($imageFile) && $imageFile["error"] != UPLOAD_ERR_OK) {
                            //There was an upload error
                            $json_response["values"] = array(
                                "dataChangeCode" => 2
                            );
                            $insertImageFlag = false;
                        }
                        if ($imageFile["size"] > $maxFileSize) {
                            //The given filesize is greater than 15mb
                            $json_response["values"] = array(
                                "dataChangeCode" => 3
                            );
                            $insertImageFlag = false;
                        }
                        if (sizeof(explode(".",clean($imageFile['name']))) > 2) {
                            //The filename contains more than one dot
                            $json_response["values"] = array(
                                "dataChangeCode" => 4
                            );
                            $insertImageFlag = false;
                        }
                        $file_Name = explode(".",clean($imageFile['name']));
                        $file_Name = clean($file_Name[0]);
                        $file_Ext = explode(".",clean($imageFile['name']));
                        $file_Ext = strtolower($file_Ext[1]);
                    } else {
                        $insertImageFlag = false;
                    }

                    if ($insertImageFlag) {
                        $indexName = md5(uniqid(rand(), true));
                        $query_args = array(
                            "filename" => $file_Name
                            ,"fileextension" => $file_Ext
                            ,"fileindex" => $indexName
                        );
                        $query = "INSERT INTO imagen (nombre_archivo,extencion_archivo,index_archivo) VALUES (:filename, :fileextension, :fileindex)";
                        $query_data_2 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");
                        logHandler(array("query" => $query_data_2[2], "action" => "insert"));

                        $newFileName = $indexName.".".$file_Ext;

                        if(!move_uploaded_file($imageFile['tmp_name'], $uploadDirectory.$newFileName)) {
                            //The given file was not able to get stored in the upload directory
                            $json_response["values"] = array(
                                "dataChangeCode" => 5
                            );
                        }
                    }
                    if ($insertImageFlag) {
                        $query_args = array(
                            "groceryid" => $groceryId
                            ,"productname" => $productName
                            ,"productprize" => $productPrize
                            ,"imageid" => $query_data_2[3]
                            ,"active" => TRUE
                        );
                    } else {
                        $query_args = array(
                            "groceryid" => $groceryId
                            ,"productname" => $productName
                            ,"productprize" => $productPrize
                            ,"imageid" => NULL
                            ,"active" => TRUE
                        );
                    }

                    $query = "INSERT INTO producto (id_almacen, nombre_producto, precio_producto, id_imagen, activo) VALUES (:groceryid, :productname, :productprize, :imageid, :active)";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                    logHandler(array("query" => $query_data[2], "action" => "insert"));

                    if (!isset($json_response["values"]["dataChangeCode"])) {
                        $json_response["values"] = array(
                            "dataChangeCode" => 1
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
            case "productUpdate":

                $maxFileSize = 1.5e+7;
                $uploadDirectory = 'imagenes/';

                $productId = $json_data->changeArguments->productId;
                $valueToUpdate = $json_data->changeArguments->valueToUpdate;
                $fieldToUpdate = $json_data->changeArguments->fieldToUpdate;
                if (isset($_FILES['filedata'])) {
                    $imageFile = ($_FILES['filedata'] != "null" ? $_FILES['filedata'] : null);
                } else {
                    $imageFile = null;
                }

                for ($x = 0; $x < sizeof($valueToUpdate); $x++) {
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                            $query = "UPDATE producto SET id_almacen = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 2:
                            $query = "UPDATE producto SET nombre_producto = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 3:
                            $query = "UPDATE producto SET precio_producto = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 4:
                            $valueToUpdate[$x] = null;

                            if ($imageFile != null) {
                                $insertImageFlag = true;
                                if (!isset($imageFile) && $imageFile["error"] != UPLOAD_ERR_OK) {
                                    //There was an upload error
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 2
                                    );
                                    $insertImageFlag = false;
                                }
                                if ($imageFile["size"] > $maxFileSize) {
                                    //The given filesize is greater than 15mb
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 3
                                    );
                                    $insertImageFlag = false;
                                }
                                if (sizeof(explode(".",clean($imageFile['name']))) > 2) {
                                    //The filename contains more than one dot
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 4
                                    );
                                    $insertImageFlag = false;
                                }
                                $file_Name = explode(".",clean($imageFile['name']));
                                $file_Name = clean($file_Name[0]);
                                $file_Ext = explode(".",clean($imageFile['name']));
                                $file_Ext = strtolower($file_Ext[1]);
                            } else {
                                $insertImageFlag = false;
                            }

                            if ($insertImageFlag) {
                                $indexName = md5(uniqid(rand(), true));
                                $query_args = array(
                                    "filename" => $file_Name
                                    , "fileextension" => $file_Ext
                                    , "fileindex" => $indexName
                                );
                                $query = "INSERT INTO imagen (nombre_archivo,extencion_archivo,index_archivo) VALUES (:filename, :fileextension, :fileindex)";
                                $query_data_1 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");
                                logHandler(array("query" => $query_data_1[2], "action" => "insert"));

                                $newFileName = $indexName . "." . $file_Ext;

                                if (!move_uploaded_file($imageFile['tmp_name'], $uploadDirectory . $newFileName)) {
                                    //The given file was not able to get stored in the upload directory
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 5
                                    );
                                } else {
                                    $valueToUpdate[$x] = $query_data_1[3];

                                    $query_args = array(
                                        "id" => $productId
                                    );
                                    $query = "SELECT CONCAT(index_archivo,'.',extencion_archivo) AS imagen_producto, imagen.id AS image_id FROM producto LEFT JOIN imagen ON producto.id_imagen = imagen.id WHERE producto.id = :id";
                                    $query_data_2 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");
                                    $query_data_2[0][0]["imagen_producto"];
                                    if ($query_data_2[0][0]["imagen_producto"] != null) {
                                        try {
                                            unlink($uploadDirectory . $query_data_2[0][0]["imagen_producto"]);
                                            $query_args = array(
                                                "imageid" => $query_data_2[0][0]["image_id"]
                                            );
                                            $query = "DELETE FROM imagen WHERE id = :imageid";
                                            $query_data_3 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                                            logHandler(array("query" => $query_data_3[2], "action" => "delete"));
                                        } catch (Exception $e) {
                                            $json_response["values"] = array(
                                                "dataChangeCode" => 6
                                            );
                                        }
                                    }
                                }
                            }
                            if ($valueToUpdate[$x] == null) {
                                $query_args = array(
                                    "id" => $productId
                                );
                                $query = "SELECT id_imagen FROM producto WHERE id = :id";
                                $query_data_4 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_04");
                                $valueToUpdate[$x] = $query_data_4[0][0]["id_imagen"];
                            }

                            $query = "UPDATE producto SET id_imagen = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 5:
                            $query = "UPDATE producto SET activo = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 6:
                            $query_args = array(
                                "id" => $productId
                            );
                            $query = "SELECT CONCAT(index_archivo,'.',extencion_archivo) AS imagen_producto, imagen.id AS image_id FROM producto LEFT JOIN imagen ON producto.id_imagen = imagen.id WHERE producto.id = :id";
                            $query_data_5 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_05");
                            $query_data_5[0][0]["imagen_producto"];
                            if ($query_data_5[0][0]["imagen_producto"] != null) {
                                try {
                                    unlink($uploadDirectory . $query_data_5[0][0]["imagen_producto"]);
                                    $query_args = array(
                                        "imageid" => $query_data_5[0][0]["image_id"]
                                    );
                                    $query = "DELETE FROM imagen WHERE id = :imageid";
                                    $query_data_6 = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_06");
                                    logHandler(array("query" => $query_data_6[2], "action" => "delete"));
                                } catch (Exception $e) {
                                    $json_response["values"] = array(
                                        "dataChangeCode" => 6
                                    );
                                }
                            }
                            $query = array(
                                "DELETE FROM producto WHERE id = :toupdateid"
                            );
                            $logAction = "delete";
                            break;
                    }
                    switch ($valueToUpdate[$x]) {
                        case "true":
                            $valueToUpdate[$x] = 1;
                            break;
                        case "false":
                            $valueToUpdate[$x] = 0;
                            break;
                    }
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                            $query_args = array(
                                "toupdateid" => $productId
                                , "toupdatevalue" => $valueToUpdate[$x]
                            );
                            break;
                        case 6:
                            $query_args = array(
                                "toupdateid" => $productId
                            );
                            break;
                    }

                    if (is_array($query)) {
                        for ($y = 0; $y < sizeof($query); $y++) {
                            $query_data = pdoExecuteQuery($pdo_mysql_rw, $query[$y], $query_args, $json_data->changeHeader . "_query_07");
                            logHandler(array("query" => $query_data[2], "action" => $logAction));
                        }
                    } else {
                        $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_08");
                        logHandler(array("query" => $query_data[2], "action" => $logAction));
                    }
                }

                if (!isset($json_response["values"]["dataChangeCode"])) {
                    $json_response["values"] = array(
                        "dataChangeCode" => 1
                    );
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "kardexRegistry":

                $productId = $json_data->changeArguments->productId;
                $movementDate = $json_data->changeArguments->movementDate;
                $movementAdded = $json_data->changeArguments->movementAdded;
                $movementRemoved = $json_data->changeArguments->movementRemoved;
                $movementReason = $json_data->changeArguments->movementReason;
                $movementTotal = $json_data->changeArguments->movementTotal;

                $query_args = array(
                    "productid" => $productId
                    ,"movementdate" => $movementDate
                );
                $query = "SELECT id FROM kardex_producto WHERE fecha_movimiento_producto = :movementdate AND id_producto = :productid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] == 0) {
                    $query_args = array(
                        "productid" => $productId
                        ,"movementdate" => $movementDate
                        , "movementadded" => $movementAdded
                        , "movementremoved" => $movementRemoved
                        , "movementreason" => $movementReason
                        , "movementtotal" => $movementTotal
                    );
                    $query = "INSERT INTO kardex_producto (id_producto, fecha_movimiento_producto, cantidad_entrada_producto, cantidad_salida_producto, descripcion_movimiento, total_producto) VALUES (:productid, :movementdate, :movementadded, :movementremoved, :movementreason, :movementtotal)";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                    logHandler(array("query" => $query_data[2], "action" => "insert"));

                    $json_response["values"] = array(
                        "dataChangeCode" => 1
                    );
                } else {
                    $json_response["values"] = array(
                        "dataChangeCode" => 0
                    );
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "saleRegistry":

                $saleDate = $json_data->changeArguments->saleDate;
                $saleTotal = $json_data->changeArguments->saleTotal;
                $paymentMethod = $json_data->changeArguments->paymentMethod;
                $products = $json_data->changeArguments->products;

                $query_args = array(
                    "employeeid" => (PERSON_ID == "" ? NULL : PERSON_ID)
                    ,"saletotalvalue" => $saleTotal
                    , "paymentid" => $paymentMethod
                    , "saledate" => $saleDate
                );
                $query = "INSERT INTO venta (id_empleado, valor_total_venta, id_tipo_pago, fecha_venta) VALUES (:employeeid, :saletotalvalue, :paymentid, :saledate)";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");
                logHandler(array("query" => $query_data[2], "action" => "insert"));

                $saleId = $query_data[3];

                for ($x = 0; $x < sizeof($products); $x++) {
                    $query_args = array(
                        "saleid" => $saleId
                        ,"productid" => $products[$x]->id
                        ,"soldamount" => $products[$x]->numberOfItems
                    );
                    $query = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad_producto_vendido) VALUES (:saleid, :productid, :soldamount)";
                    pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");
                    logHandler(array("query" => $query_data[2], "action" => "insert"));

                    $query_args = array(
                        "productid" => $products[$x]->id
                    );
                    $query = "SELECT total_producto FROM kardex_producto WHERE id_producto = :productid ORDER BY fecha_movimiento_producto DESC, kardex_producto.id DESC LIMIT 1";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_03");
                    if ($query_data[1] > 0) {
                        $currentProductStored = $query_data[0][0]["total_producto"];
                    } else {
                        $currentProductStored = 0;
                    }

                    $query_args = array(
                        "productid" => $products[$x]->id
                        ,"movementdate" => $saleDate
                        , "movementadded" => 0
                        , "movementremoved" => $products[$x]->numberOfItems
                        , "movementreason" => "Venta de producto, id_venta: $saleId"
                        , "movementtotal" => ($currentProductStored - $products[$x]->numberOfItems)
                    );
                    $query = "INSERT INTO kardex_producto (id_producto, fecha_movimiento_producto, cantidad_entrada_producto, cantidad_salida_producto, descripcion_movimiento, total_producto) VALUES (:productid, :movementdate, :movementadded, :movementremoved, :movementreason, :movementtotal)";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_04");
                    logHandler(array("query" => $query_data[2], "action" => "insert"));
                }

                $json_response["values"] = array(
                    "dataChangeCode" => 1
                );

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