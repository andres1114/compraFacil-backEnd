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

		$pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "root", 'db_pass' => '', 'db_name' => "compraFacil"));

        switch ($json_data->changeHeader) {
            case "personRegistry":

                $personId = $json_data->changeArguments->personId;
                $personIdType = 1;
                $personFirstName = $json_data->changeArguments->personFirstName;
                $personLastName = $json_data->changeArguments->personLastName;
                $personPhoneNumber = $json_data->changeArguments->personPhoneNumber;
                $personCellphoneNumber = $json_data->changeArguments->personCellphoneNumber;
                $personAddress = $json_data->changeArguments->personAddress;
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
                        , "personphone" => $personPhoneNumber
                        , "personcellphone" => $personCellphoneNumber
                        , "personaddress" => $personAddress
                    );
                    $query = "INSERT INTO empleado (id_tipo_identificacion, numero_identificacion_empleado, nombre_empleado, apellido_empleado, telefono_fijo_empleado, telefono_celular_empleado, direccion_residencia_empleado, fecha_registro_empleado, activo) VALUES (:personidtype, :personid, :personfirstname, :personlastname, :personphone, :personcellphone, :personaddress, NOW(), TRUE)";
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
                        case 4:
                            $query = "UPDATE empleado SET telefono_fijo_empleado = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 5:
                            $query = "UPDATE empleado SET telefono_celular_empleado = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 6:
                            $query = "UPDATE empleado SET direccion_residencia_empleado = :toupdatevalue WHERE id = :toupdateid";
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
            case "providerRegistry":

                $providerCityId = $json_data->changeArguments->providerCityId;
                $providerId = $json_data->changeArguments->providerId;
                $providerIdType = $json_data->changeArguments->providerIdType;
                $providerName = $json_data->changeArguments->providerName;
                $providerPhone = $json_data->changeArguments->providerPhone;

                $query_args = array(
                    "providerid" => $providerId
                );
                $query = "SELECT id FROM proveedor WHERE numero_identificacion_proveedor = :providerid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] == 0) {
                    $query_args = array(
                        "providerid" => $providerId
                        ,"providertypeid" => $providerIdType
                    );
                    $query = "SELECT id FROM empleado WHERE numero_identificacion_empleado = :providerid AND id_tipo_identificacion = :providertypeid";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_02");

                    if ($query_data[1] == 0) {
                        $query_args = array(
                            "providertypeid" => $providerIdType
                            , "providerid" => $providerId
                            , "providername" => $providerName
                            , "providerphone" => $providerPhone
                            , "providercityid" => $providerCityId
                        );
                        $query = "INSERT INTO proveedor (id_tipo_identificacion, numero_identificacion_proveedor, nombre_proveedor, telefono_proveedor, id_ciudad, activo) VALUES (:providertypeid, :providerid, :providername, :providerphone, :providercityid, TRUE)";
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
            case "providerUpdate":

                $providerId = $json_data->changeArguments->providerId;
                $valueToUpdate = $json_data->changeArguments->valueToUpdate;
                $fieldToUpdate = $json_data->changeArguments->fieldToUpdate;

                for ($x = 0; $x < sizeof($valueToUpdate); $x++) {
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                            $query = "UPDATE proveedor SET id_ciudad = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 2:
                            $query = "UPDATE proveedor SET id_tipo_identificacion = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 3:
                            $query = "UPDATE proveedor SET numero_identificacion_proveedor = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 4:
                            $query = "UPDATE proveedor SET nombre_proveedor = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 5:
                            $query = "UPDATE proveedor SET telefono_proveedor = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 6:
                            $query = "UPDATE proveedor SET activo = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 7:
                            $query = array(
                                "DELETE FROM proveedor WHERE id = :toupdateid"
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
                            $query_args = array(
                                "toupdateid" => $providerId
                                , "toupdatevalue" => $valueToUpdate[$x]
                            );
                            break;
                        case 7:
                            $query_args = array(
                                "toupdateid" => $providerId
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
            case "productRegistry":

                $productProviderId = $json_data->changeArguments->productProviderId;
                $productTypeId = $json_data->changeArguments->productTypeId;
                $productName = $json_data->changeArguments->productName;
                $productPrize = $json_data->changeArguments->productPrize;

                $query_args = array(
                    "productname" => $productName
                    ,"producttypeid" => $productTypeId
                    ,"productproviderid" => $productProviderId
                );
                $query = "SELECT id FROM producto WHERE nombre_producto = :productname AND id_tipo_producto = :producttypeid AND id_proveedor = :productproviderid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->changeHeader . "_query_01");

                if ($query_data[1] == 0) {
                    $query_args = array(
                        "producttypeid" => $productTypeId
                        , "providerid" => $productProviderId
                        , "productname" => $productName
                        , "productprize" => $productPrize
                    );
                    $query = "INSERT INTO producto (id_tipo_producto, id_proveedor, nombre_producto, precio_venta_producto, activo) VALUES (:producttypeid, :providerid, :productname, :productprize, TRUE)";
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
            case "productUpdate":

                $productId = $json_data->changeArguments->productId;
                $valueToUpdate = $json_data->changeArguments->valueToUpdate;
                $fieldToUpdate = $json_data->changeArguments->fieldToUpdate;

                for ($x = 0; $x < sizeof($valueToUpdate); $x++) {
                    switch ($fieldToUpdate[$x]) {
                        case 1:
                            $query = "UPDATE producto SET id_proveedor = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 2:
                            $query = "UPDATE producto SET id_tipo_producto = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 3:
                            $query = "UPDATE producto SET nombre_producto = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 4:
                            $query = "UPDATE producto SET precio_venta_producto = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 5:
                            $query = "UPDATE producto SET activo = :toupdatevalue WHERE id = :toupdateid";
                            $logAction = "update";
                            break;
                        case 6:
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