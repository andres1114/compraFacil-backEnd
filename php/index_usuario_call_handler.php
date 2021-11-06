<?php

    $session_handler_include_origin = "back_end";
    $session_handler_permission_code = 2;
    //include_once "session_handler.php";
    include_once "db_connection.php";

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
            case "searchProduct":

                $query_args = array(
                    "productName" => "%".preg_replace("/\s/", "%", $json_data->callArguments->productName)."%"
                );
                $query = "SELECT 
                        pr.id, 
                        al.nombre_almacen, 
                        pr.nombre_producto, 
                        pr.precio_producto,
                        CONCAT(i1.index_archivo,'.',i1.extencion_archivo) AS imagen_almacen,
                        CONCAT(i2.index_archivo,'.',i2.extencion_archivo) AS imagen_producto
                    FROM producto AS pr 
                        INNER JOIN almacen AS al 
                            ON al.id = pr.id_almacen
                        LEFT JOIN imagen i1 
                            ON al.id_imagen = i1.id
                        LEFT JOIN imagen i2 
                            ON pr.id_imagen = i2.id
                    WHERE
                        pr.nombre_producto LIKE :productName
                    AND 
                        pr.activo IS TRUE  
                    ORDER BY 
                        pr.precio_producto ASC";

                $query_data = pdoExecuteQuery($pdo_mysql_rw,$query,$query_args,$json_data->callHeader."_query_01");

                $json_response["values"] = array(
                    "groceryName" => array()
                    ,"productName" => array()
                    ,"productPrize" => array()
                    ,"productImage" => array()
                    ,"groceryImage" => array()
                );

                for ($x = 0; $x < $query_data[1]; $x++) {
                    $json_response["values"]["groceryName"][$x] = $query_data[0][$x]["nombre_almacen"];
                    $json_response["values"]["productName"][$x] = $query_data[0][$x]["nombre_producto"];
                    $json_response["values"]["productPrize"][$x] = $query_data[0][$x]["precio_producto"];
                    $json_response["values"]["productImage"][$x] = $query_data[0][$x]["imagen_producto"];
                    $json_response["values"]["groceryImage"][$x] = $query_data[0][$x]["imagen_almacen"];
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