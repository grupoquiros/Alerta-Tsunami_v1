<?php
/* ****************************************
   * Rutas de la sección USUARIOS         *
   ****************************************
*/

$app->group('/usuarios', function () use ($app, $settings, $isLogged, $authenticate) {

    $app->get('/', $authenticate($app, $settings), function() use ($app) {
		$id = $_SESSION['user'];
		$user_dates = Users::where('email', '=', $id)->first();
		$user_id=$user_dates['id'];

		
		
        $app->render('usuarios_home.html', array('user_id' => $user_id));		
		//$app->render('usuarios_home.html');
    });	


    $app->get('/logout', $authenticate($app, $settings), function() use ($app, $settings) {
        unset($_SESSION['user']);
        unset($_SESSION['id_empresa_activa']);		
        $app->view()->setData('user', null);
        $app->redirect($settings->base_url);
    });
	


/* PARÁMETROS GESTIÓN DE USUARIOS */	


	
/* EDICIÓN */

    $app->get('/editar/:id', $authenticate($app, $settings), function($id) use ($app) {
        $flash = $app->view()->getData('flash');
        $error = isset($flash['error']) ? $flash['error'] : '';

        $u = Users::where('id', '=', $id)->first();
        $app->render('usuarios_usuarios_editar.html', array('u' => $u, 'error' => $error));
    })->conditions(array('id' => '\d+'));

    $app->post('/editar/:id', $authenticate($app, $settings), function($id) use ($app, $settings) {
        $username 	= 	$app->request->post('username');
        $apellidos 	= 	$app->request->post('apellidos');
        $telefono 	= 	$app->request->post('telefono');
        $zona 		= 	$app->request->post('zona');
        $pass 		= 	$app->request->post('password');
        $password 	= 	hash('sha512', $pass );
	
        $redirect = $settings->base_url . '/usuarios';

        if( !empty($pass) ) {
            Users::where('id', '=', $id)->update(array('nombre' => $username, 'apellidos' => $apellidos, 'password' => $password, 'telefono' => $telefono, 'zona' => $zona));
        } else {
            Users::where('id', '=', $id)->update(array('nombre' => $username, 'apellidos' => $apellidos, 'telefono' => $telefono, 'zona' => $zona));
        }

        $app->render('usuarios_success.html', array('redirect' => $redirect));
    })->conditions(array('id' => '\d+'));

	
/* FIN PARAMETRO GESTION USUARIOS */ 
	

});

