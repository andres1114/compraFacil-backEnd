<?php

    $session_handler_include_origin = "back_end";
    $session_handler_permission_code = array(1,2);
    include_once "session_handler.php";

    $json_response = Array();

    ob_start();

    try {

        if (isset($_POST['jsondata'])) {
            $json_data = json_decode($_POST['jsondata']);
        } else {
            $json_data = json_decode(file_get_contents('php://input'));
        }

		$pdo_mysql_rw = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "localhost", 'db_user' => "root", 'db_pass' => 'root', 'db_name' => "compraFacil"));

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
            case "showPersonIdType":

                $query_args = array();
                $query = "SELECT id, nombre_tipo_identificacion FROM tipo_identificacion ORDER BY id ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    , "name" => array()
                );

                if ($query_data[1] > 0) {
                    for ($x = 0; $x < $query_data[1]; $x++) {
                        $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                        $json_response["values"]["name"][$x] = $query_data[0][$x]["nombre_tipo_identificacion"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showCities":

                $query_args = array();
                $query = "SELECT ciudad.id AS city_id, nombre_ciudad, nombre_pais FROM ciudad INNER JOIN pais ON ciudad.id_pais = pais.id ORDER BY nombre_pais ASC, nombre_ciudad ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    , "name" => array()
                );

                if ($query_data[1] > 0) {
                    for ($x = 0; $x < $query_data[1]; $x++) {
                        $json_response["values"]["id"][$x] = $query_data[0][$x]["city_id"];
                        $json_response["values"]["name"][$x] = "(".$query_data[0][$x]["nombre_pais"].") ".$query_data[0][$x]["nombre_ciudad"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showUserType":

                $query_args = array();
                $query = "SELECT id, descripcion_tipo_usuario FROM tipo_usuario ORDER BY valor_tipo_usuario DESC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    , "name" => array()
                );

                if ($query_data[1] > 0) {
                    for ($x = 0; $x < $query_data[1]; $x++) {
                        $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                        $json_response["values"]["name"][$x] = $query_data[0][$x]["descripcion_tipo_usuario"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showPayTypes":

                $query_args = array();
                $query = "SELECT id, nombre_tipo_pago FROM tipo_pago ORDER BY nombre_tipo_pago DESC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    , "name" => array()
                );

                if ($query_data[1] > 0) {
                    for ($x = 0; $x < $query_data[1]; $x++) {
                        $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                        $json_response["values"]["name"][$x] = $query_data[0][$x]["nombre_tipo_pago"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showPersons":

                $query_args = array();
                $query = "SELECT id, numero_identificacion_empleado, CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre_completo, activo FROM empleado ORDER BY activo DESC, nombre_completo ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    , "personId" => array()
                    , "personFullName" => array()
                    , "personCellphone" => array()
                    , "active" => array()
                );

                if ($query_data[1] > 0) {
                    for ($x = 0; $x < $query_data[1]; $x++) {
                        $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                        $json_response["values"]["personId"][$x] = $query_data[0][$x]["numero_identificacion_empleado"];
                        $json_response["values"]["personFullName"][$x] = $query_data[0][$x]["nombre_completo"];
                        $json_response["values"]["active"][$x] = $query_data[0][$x]["activo"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showUserlessPerson":

                $query_args = array();
                $query = "SELECT id, CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre_completo FROM empleado WHERE activo = TRUE AND id NOT IN (SELECT id_empleado FROM empleado_usuario) ORDER BY nombre_completo ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    , "personFullName" => array()
                );

                if ($query_data[1] > 0) {
                    for ($x = 0; $x < $query_data[1]; $x++) {
                        $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                        $json_response["values"]["name"][$x] = $query_data[0][$x]["nombre_completo"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showPersonData":

                include_once "php_js_encryption.php";
                $Encryption = new Encryption();

                $query_args = array(
                    "personid" => $json_data->callArguments->personId
                );
                $query = "SELECT id, numero_identificacion_empleado, nombre_empleado, apellido_empleado, fecha_registro_empleado, activo FROM empleado WHERE id = :personid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => ""
                    , "personId" => ""
                    , "userId" => ""
                    , "personFirstName" => ""
                    , "personLastName" => ""
                    , "personUserName" => ""
                    , "personUserPassword" => ""
                    , "personRegistrationDate" => ""
                    , "isPersonActive" => ""
                    , "isUserActive" => ""
                    , "isUserRegistered" => ""
                );

                if ($query_data[1] > 0) {
                    $json_response["values"]["id"] = $query_data[0][0]["id"];
                    $json_response["values"]["personId"] = $query_data[0][0]["numero_identificacion_empleado"];
                    $json_response["values"]["personFirstName"] = $query_data[0][0]["nombre_empleado"];
                    $json_response["values"]["personLastName"] = $query_data[0][0]["apellido_empleado"];
                    $json_response["values"]["personRegistrationDate"] = $query_data[0][0]["fecha_registro_empleado"];
                    $json_response["values"]["isPersonActive"] = $query_data[0][0]["activo"];

                    $query_args = array(
                        "personid" => $json_data->callArguments->personId
                    );
                    $query = "SELECT usuario.id AS user_id, nombre_usuario, contrasena_sha256, activo FROM usuario INNER JOIN empleado_usuario ON usuario.id = empleado_usuario.id_usuario WHERE empleado_usuario.id_empleado = :personid";
                    $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_02");

                    if ($query_data[1] > 0) {
                        $json_response["values"]["isUserRegistered"] = 1;
                        $json_response["values"]["userId"] = $query_data[0][0]["user_id"];
                        $json_response["values"]["personUserName"] = $query_data[0][0]["nombre_usuario"];
                        $json_response["values"]["personUserPassword"] = $Encryption->encrypt(encryptControl("decrypt", $query_data[0][0]["contrasena_sha256"], BLOWFISH_VALUE),NONCE_VALUE);
                        $json_response["values"]["isUserActive"] = $query_data[0][0]["activo"];

                    } else {
                        $json_response["values"]["isUserRegistered"] = 0;
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showUsers":

                include_once "php_js_encryption.php";
                $Encryption = new Encryption();

                $query_args = array();
                $query = "SELECT usuario.id AS user_id, nombre_usuario, contrasena_sha256, activo, descripcion_tipo_usuario FROM usuario INNER JOIN tipo_usuario ON usuario.id_tipo_usuario = tipo_usuario.id ORDER BY activo DESC, id_tipo_usuario ASC, nombre_usuario ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"personUserName" => array()
                    ,"personUserPassword" => array()
                    ,"isUserActive" => array()
                    ,"personUserType" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["user_id"];
                    $json_response["values"]["personUserName"][$x] = $query_data[0][$x]["nombre_usuario"];
                    $json_response["values"]["personUserPassword"][$x] = $Encryption->encrypt(encryptControl("decrypt", $query_data[0][$x]["contrasena_sha256"], BLOWFISH_VALUE),NONCE_VALUE);
                    $json_response["values"]["active"][$x] = $query_data[0][$x]["activo"];
                    $json_response["values"]["personUserType"][$x] = $query_data[0][$x]["descripcion_tipo_usuario"];
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showUserData":

                include_once "php_js_encryption.php";
                $Encryption = new Encryption();

                $query_args = array(
                    "userid" => $json_data->callArguments->userId
                );
                $query = "SELECT usuario.id AS user_id, nombre_usuario, contrasena_sha256, tipo_usuario.id AS user_type_id FROM usuario INNER JOIN tipo_usuario ON usuario.id_tipo_usuario = tipo_usuario.id WHERE usuario.id = :userid ORDER BY activo DESC, nombre_usuario ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => ""
                    ,"personUserName" => ""
                    ,"personUserPassword" => ""
                    ,"personUserType" => ""
                    ,"personName" => ""
                );

                $json_response["values"]["id"] = $query_data[0][0]["user_id"];
                $json_response["values"]["personUserName"] = $query_data[0][0]["nombre_usuario"];
                $json_response["values"]["personUserPassword"] = $Encryption->encrypt(encryptControl("decrypt", $query_data[0][0]["contrasena_sha256"], BLOWFISH_VALUE),NONCE_VALUE);
                $json_response["values"]["personUserType"] = $query_data[0][0]["user_type_id"];

                $query_args = array(
                    "userid" => $json_data->callArguments->userId
                );
                $query = "SELECT CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre_completo FROM empleado INNER JOIN empleado_usuario ON empleado.id = empleado_usuario.id_empleado WHERE empleado_usuario.id_usuario = :userid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_02");

                if ($query_data[1] > 0) {
                    $json_response["values"]["personName"] = $query_data[0][0]["nombre_completo"];
                } else {
                    $json_response["values"]["personName"] = "Sin empleado";
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showGroceries":

                $query_args = array();
                $query = "SELECT almacen.id AS id_almacen, nombre_almacen, pagina_web_almacen, CONCAT(index_archivo,'.',extencion_archivo) AS imagen_almacen, activo FROM almacen LEFT JOIN imagen ON almacen.id_imagen = imagen.id ORDER BY activo DESC, nombre_almacen ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"groceryName" => array()
                    ,"groceryWebsite" => array()
                    ,"groceryImage" => array()
                    ,"active" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["id_almacen"];
                    $json_response["values"]["name"][$x] = $query_data[0][$x]["nombre_almacen"];
                    $json_response["values"]["groceryWebsite"][$x] = $query_data[0][$x]["pagina_web_almacen"];
                    $json_response["values"]["groceryImage"][$x] = $query_data[0][$x]["imagen_almacen"];
                    $json_response["values"]["active"][$x] = $query_data[0][$x]["activo"];
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showGroceryData":

                $query_args = array(
                    "groceryid" => $json_data->callArguments->providerId
                );
                $query = "SELECT almacen.id AS id_almacen, nombre_almacen, pagina_web_almacen, CONCAT(index_archivo,'.',extencion_archivo) AS imagen_almacen FROM almacen LEFT JOIN imagen ON almacen.id_imagen = imagen.id WHERE almacen.id = :groceryid";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => ""
                    ,"groceryName" => ""
                    ,"groceryWebsite" => ""
                    ,"groceryImage" => ""
                );

                $json_response["values"]["id"] = $query_data[0][0]["id_almacen"];
                $json_response["values"]["groceryName"] = $query_data[0][0]["nombre_almacen"];
                $json_response["values"]["groceryWebsite"] = $query_data[0][0]["pagina_web_almacen"];
                $json_response["values"]["groceryImage"] = $query_data[0][0]["imagen_almacen"];

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showProductTypes":

                $query_args = array();
                $query = "SELECT id, nombre_tipo_producto FROM tipo_producto ORDER BY nombre_tipo_producto ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    , "name" => array()
                );

                if ($query_data[1] > 0) {
                    for ($x = 0; $x < $query_data[1]; $x++) {
                        $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                        $json_response["values"]["name"][$x] = $query_data[0][$x]["nombre_tipo_producto"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showProducts":

                $query_args = array();
                $query = "SELECT pr.id, al.nombre_almacen, pr.nombre_producto, pr.precio_producto, CONCAT(im.index_archivo,'.',im.extencion_archivo) AS imagen_producto, pr.activo FROM producto AS pr INNER JOIN almacen AS al ON al.id = pr.id_almacen LEFT JOIN imagen AS im ON im.id = pr.id_imagen ORDER BY activo ASC, al.nombre_almacen ASC, nombre_producto ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"groceryName" => array()
                    ,"productName" => array()  
                    ,"productPrize" => array()
                    ,"productImage" => array()
                    ,"active" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                    $json_response["values"]["groceryName"][$x] = $query_data[0][$x]["nombre_almacen"];
                    $json_response["values"]["productName"][$x] = $query_data[0][$x]["nombre_producto"];
                    $json_response["values"]["productPrize"][$x] = $query_data[0][$x]["precio_producto"];
                    $json_response["values"]["productImage"][$x] = $query_data[0][$x]["imagen_producto"];
                    $json_response["values"]["active"][$x] = $query_data[0][$x]["activo"];
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showProductData":

                $query_args = array(
                    "productId" => $json_data->callArguments->productId
                );
                $query = "SELECT pr.id, al.id AS almacen, pr.nombre_producto, pr.precio_producto, CONCAT(im.index_archivo,'.',im.extencion_archivo) AS imagen_producto FROM producto AS pr INNER JOIN almacen AS al ON al.id = pr.id_almacen LEFT JOIN imagen AS im ON im.id = pr.id_imagen WHERE pr.id = :productId";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => ""
                    ,"groceryId" => ""
                    ,"productName" => ""
                    ,"productPrize" => ""
                    ,"productImage" => ""
                );

                $json_response["values"]["id"] = $query_data[0][0]["id"];
                $json_response["values"]["groceryId"] = $query_data[0][0]["almacen"];
                $json_response["values"]["productName"] = $query_data[0][0]["nombre_producto"];
                $json_response["values"]["productPrize"] = $query_data[0][0]["precio_producto"];
                $json_response["values"]["productImage"] = $query_data[0][0]["imagen_producto"];

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showProductsForKardex":

                $query_args = array();
                $query = "SELECT producto.id AS product_id, nombre_producto, nombre_tipo_producto, nombre_proveedor, producto.activo FROM producto INNER JOIN tipo_producto ON producto.id_tipo_producto = tipo_producto.id INNER JOIN proveedor ON producto.id_proveedor = proveedor.id WHERE producto.activo = TRUE ORDER BY nombre_tipo_producto ASC, nombre_producto ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"productName" => array()
                    ,"productType" => array()
                    ,"productProvider" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["product_id"];
                    $json_response["values"]["productName"][$x] = $query_data[0][$x]["nombre_producto"];
                    $json_response["values"]["productType"][$x] = $query_data[0][$x]["nombre_tipo_producto"];
                    $json_response["values"]["productProvider"][$x] = $query_data[0][$x]["nombre_proveedor"];
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showKardexData":

                $query_args = array(
                    "productid" => $json_data->callArguments->productId
                );
                $query = "SELECT id, cantidad_entrada_producto, cantidad_salida_producto, total_producto, descripcion_movimiento, fecha_movimiento_producto FROM kardex_producto WHERE id_producto = :productid ORDER BY fecha_movimiento_producto DESC, id DESC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"currentProductAmount" => 0
                    ,"productAdded" => array()
                    ,"productRemoved" => array()
                    ,"totalProduct" => array()
                    ,"dateOfMovement" => array()
                    ,"reasonOfMovement" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                    $json_response["values"]["productAdded"][$x] = $query_data[0][$x]["cantidad_entrada_producto"];
                    $json_response["values"]["productRemoved"][$x] = $query_data[0][$x]["cantidad_salida_producto"];
                    $json_response["values"]["totalProduct"][$x] = $query_data[0][$x]["total_producto"];
                    $json_response["values"]["dateOfMovement"][$x] = $query_data[0][$x]["fecha_movimiento_producto"];
                    $json_response["values"]["reasonOfMovement"][$x] = $query_data[0][$x]["descripcion_movimiento"];

                    if ($x == 0) {
                        $json_response["values"]["currentProductAmount"] = $query_data[0][$x]["total_producto"];
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showProductsToSell":

                $query_args = array();
                $query = "SELECT producto.id AS product_id, CONCAT(nombre_producto, ' (',nombre_proveedor,'), $', precio_venta_producto) AS product_name, precio_venta_producto FROM producto INNER JOIN proveedor ON producto.id_proveedor = proveedor.id WHERE producto.activo = TRUE ORDER BY nombre_producto ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"productName" => array()
                    ,"productPrize" => array()
                    ,"productStoredQuantity" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["product_id"];
                    $json_response["values"]["productName"][$x] = $query_data[0][$x]["product_name"];
                    $json_response["values"]["productPrize"][$x] = $query_data[0][$x]["precio_venta_producto"];

                    $query_args = array(
                        "productid" => $query_data[0][$x]["product_id"]
                    );
                    $query = "SELECT total_producto FROM kardex_producto WHERE id_producto = :productid ORDER BY fecha_movimiento_producto DESC, kardex_producto.id DESC LIMIT 1";
                    $inner_query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_02");

                    if ($inner_query_data[1] > 0) {
                        $json_response["values"]["productStoredQuantity"][$x] = $inner_query_data[0][0]["total_producto"];
                    } else {
                        $json_response["values"]["productStoredQuantity"][$x] = 0;
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showSalesReport":

                if ($json_data->callArguments->paytypeId == 0) {
                    $query_args = array(
                        "datestart" => $json_data->callArguments->dateStart
                        ,"dateend" => $json_data->callArguments->dateEnd
                    );
                    $query = "SELECT venta.id, valor_total_venta, nombre_tipo_pago, fecha_venta FROM venta INNER JOIN tipo_pago ON venta.id_tipo_pago = tipo_pago.id WHERE fecha_venta BETWEEN :datestart AND :dateend ORDER BY fecha_venta DESC";
                } else {
                    $query_args = array(
                        "datestart" => $json_data->callArguments->dateStart
                        ,"dateend" => $json_data->callArguments->dateEnd
                        ,"paytypeid" => $json_data->callArguments->paytypeId
                    );
                    $query = "SELECT venta.id, valor_total_venta, nombre_tipo_pago, fecha_venta FROM venta INNER JOIN tipo_pago ON venta.id_tipo_pago = tipo_pago.id WHERE fecha_venta BETWEEN :datestart AND :dateend AND venta.id_tipo_pago = :paytypeid ORDER BY fecha_venta DESC";
                }

                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"totalSale" => array()
                    ,"payType" => array()
                    ,"saleDate" => array()
                    ,"saleDetail" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["id"];
                    $json_response["values"]["totalSale"][$x] = $query_data[0][$x]["valor_total_venta"];
                    $json_response["values"]["payType"][$x] = $query_data[0][$x]["nombre_tipo_pago"];
                    $json_response["values"]["saleDate"][$x] = $query_data[0][$x]["fecha_venta"];

                    $query_args = array(
                        "saleid" => $query_data[0][$x]["id"]
                    );
                    $query = "SELECT detalle_venta.id, nombre_producto, precio_venta_producto, cantidad_producto_vendido FROM detalle_venta INNER JOIN producto ON detalle_venta.id_producto = producto.id WHERE id_venta = :saleid ORDER BY nombre_producto ASC";
                    $inner_query_data = pdoExecuteQuery($pdo_mysql_rw, $query, $query_args, $json_data->callHeader . "_query_02");

                    $json_response["values"]["saleDetail"][$x] = array(
                        "id" => array()
                        , "productName" => array()
                        , "productPrice" => array()
                        , "productsSold" => array()
                        , "totalProductsSold" => array()
                    );

                    for ($y = 0; $y < $inner_query_data[1]; $y++) {
                        $json_response["values"]["saleDetail"][$x]["id"][$y] = $inner_query_data[0][$y]["id"];
                        $json_response["values"]["saleDetail"][$x]["productName"][$y] = $inner_query_data[0][$y]["nombre_producto"];
                        $json_response["values"]["saleDetail"][$x]["productPrice"][$y] = $inner_query_data[0][$y]["precio_venta_producto"];
                        $json_response["values"]["saleDetail"][$x]["productsSold"][$y] = $inner_query_data[0][$y]["cantidad_producto_vendido"];
                        $json_response["values"]["saleDetail"][$x]["totalProductsSold"][$y] = ($inner_query_data[0][$y]["cantidad_producto_vendido"] * $inner_query_data[0][$y]["precio_venta_producto"]);
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showKardexReport":

                $query_args = array();
                $query = "SELECT producto.id AS product_id, nombre_producto, nombre_tipo_producto, nombre_proveedor, producto.activo FROM producto INNER JOIN tipo_producto ON producto.id_tipo_producto = tipo_producto.id INNER JOIN proveedor ON producto.id_proveedor = proveedor.id WHERE producto.activo = TRUE ORDER BY nombre_tipo_producto ASC, nombre_producto ASC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "id" => array()
                    ,"productName" => array()
                    ,"productType" => array()
                    ,"productProvider" => array()
                    ,"productStoredQuantity" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["id"][$x] = $query_data[0][$x]["product_id"];
                    $json_response["values"]["productName"][$x] = $query_data[0][$x]["nombre_producto"];
                    $json_response["values"]["productType"][$x] = $query_data[0][$x]["nombre_tipo_producto"];
                    $json_response["values"]["productProvider"][$x] = $query_data[0][$x]["nombre_proveedor"];

                    $query_args = array(
                        "productid" => $query_data[0][$x]["product_id"]
                    );
                    $query = "SELECT total_producto FROM kardex_producto WHERE id_producto = :productid ORDER BY fecha_movimiento_producto DESC, kardex_producto.id DESC LIMIT 1";
                    $inner_query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_02");

                    if ($inner_query_data[1] > 0) {
                        $json_response["values"]["productStoredQuantity"][$x] = $inner_query_data[0][0]["total_producto"];
                    } else {
                        $json_response["values"]["productStoredQuantity"][$x] = 0;
                    }
                }

                $json_response["statusCode"] = 200;
                $json_response["errorMessage"] = null;

                break;
            case "showLog":

                $query_args = array();
                $query = "SELECT * FROM log ORDER BY timestamp DESC";
                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "username" => array()
                    ,"timestamp" => array()
                    ,"logaction" => array()
                    ,"logquery" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["username"][$x] = $query_data[0][$x]["user_name"];
                    $json_response["values"]["timestamp"][$x] = $query_data[0][$x]["timestamp"];
                    $json_response["values"]["logaction"][$x] = $query_data[0][$x]["logaction"];
                    $json_response["values"]["logquery"][$x] = $query_data[0][$x]["logquery"];
                }

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