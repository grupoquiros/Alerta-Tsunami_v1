<?php

/* *************
**  API REST  **
************** /

/**
 * Mostramos la respuesta en formato json al cliente
 * @param String $status_code   Código de respuesta Http
 * @param Int    $response      Respuesta Json
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Código de respuesta Http
    $app->status($status_code);

    // Adaptamos el contenido de la respuesta a formato JSON
    $app->contentType('application/json');

    echo json_encode($response);
}


// LISTADO USUARIOS
    $app->get('/api/v1/usuarios/listado', function() use ($app) {
        $usuarios = Users::orderBy('apellidos', 'asc')->get();
        $arr = array();
        foreach ($usuarios as $usuario) {

			// Damos formato a la fecha
			$fecha_original = $usuario['creacion'];
			$invert = explode("-",$fecha_original);
			$usuario['date'] = $invert[2]."-".$invert[1]."-".$invert[0];
			
            $usuario['url'] = $app->request->getUrl() . $app->request->getPath() . 'usuarios/' . $usuario['id'];
            $arr[] = $usuario;
        }
		json_encode($usuarios);
        echoRespnse(200, $usuarios);
    });

	// LISTADO DE ALERTAS
    $app->get('/api/v1/alertas/listado', function() use ($app) {
        $alertas = Posts::orderBy('fecha_alerta', 'asc')->get();
		json_encode($alertas);
        echoRespnse(200, $alertas);
    });

	

