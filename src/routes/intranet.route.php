<?php
/* ****************************************
   * Rutas de la sección INTRANET         *
   ****************************************
*/

$app->group('/intranet', function () use ($app, $settings, $isAdminLogged, $adminAuthenticate) {


    $app->get('/', $adminAuthenticate($app, $settings), function() use ($app) {
		$app->render('home_intranet.html');
    });	


    $app->get('/logout', $adminAuthenticate($app, $settings), function() use ($app, $settings) {
        unset($_SESSION['admin']);
        $app->view()->setData('admin', null);
        $app->redirect($settings->base_url);
    });

	
	
	// ********************************** //
	// *** FUNCIONALIDADES DE ALERTAS *** //
	// ********************************** //
	
	// ALTA DE ALERTAS
	
    $app->get('/alertas/listado', $adminAuthenticate($app, $settings), function() use ($app) {
        $posts = Posts::orderBy('fecha_alerta', 'desc')->get();
        $arr = array();
        foreach ($posts as $post) {

			// Damos formato a la fecha
			$fecha_original = $post['fecha_alerta'];
			$invert = explode("-",$fecha_original);
			$post['date'] = $invert[2]."-".$invert[1]."-".$invert[0];
			
            $post['url'] = $app->request->getUrl() . $app->request->getPath() . 'post/' . $post['id'];
            $arr[] = $post;
        }
        $app->render('intranet_blog_listado.html', array('posts' => $arr));
    });

	
    $app->get('/alertas/nuevo', $adminAuthenticate($app, $settings), function() use ($app) {
        $flash = $app->view()->getData('flash');
        $error = isset($flash['error']) ? $flash['error'] : '';

		
        $app->render('intranet_blog_nuevo.html', array('error' => $error));
    });

    $app->post('/alertas/nuevo', $adminAuthenticate($app, $settings), function() use ($app, $settings) {
	
		// Extraemos los valores de los campos introducidos en el Formulario
	    $fecha_alerta 		    = 	$app->request->post('campo_fecha_alerta');
		$hora_alerta 		    = 	$app->request->post('campo_hora_alerta');
		$nivel_alerta			= 	$app->request->post('campo_nivel_alerta');
		$zonas_afectadas		= 	$app->request->post('campo_zonas');		
		$resumen 				= 	$app->request->post('campo_resumen');				
        $detalle 				= 	$app->request->post('campo_detalle');

		// Asignamos la fecha
		$created_at 			= 	date('Y-m-d H:i:s');		
		$user_id 				= 	Users::get_id($_SESSION['admin']);		
		$slug 					= 	$fecha_alerta.$hora_alerta;	
		$estado_alerta			= 	"1";	
		
		// Define la ruta de la redirección
        $redirect = $app->request->post('redirect');

        if ($fecha_alerta == "") {
            $app->flash('error', 1);
            $app->redirect($settings->base_url . '/intranet/alertas/nuevo');
        }
        if ($hora_alerta == "") {
            $app->flash('error', 2);
            $app->redirect($settings->base_url . '/intranet/alertas/nuevo');
        }

		// Invertimos la fecha para adaptarla al formato válido.
		$fecha = $fecha_alerta;
		$invert = explode("-",$fecha);
		$fecha_invertida = $invert[2]."-".$invert[1]."-".$invert[0];

        Posts::insert(array(
		'fecha_alerta'  	 => 	$fecha_invertida, 
		'hora_alerta'  	     => 	$hora_alerta,
		'zonas'				 => 	$zonas_afectadas, 
		'resumen'		 	 => 	$resumen, 		
		'detalle' 			 => 	$detalle,		
		'nivel_alarma' 		 => 	$nivel_alerta,		
		'slug'				 => 	$slug,
		'estado_alarma'		 => 	$estado_alerta,		
		'user_id' 		 	 => 	$user_id,		
		'created_at' 		 => 	$created_at		
		));

        $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
    });

	
	// ACTIVAR Y DESACTIVAR ALERTAS
    $app->get('/alertas/activar/:id', $adminAuthenticate($app, $settings), function($id) use ($app, $settings) {
        $post = Posts::where('id', '=', $id)->first();

        if($post){
            $redirect = $settings->base_url . '/intranet/alertas/listado';
            Posts::where('id', '=', $id)->update(array('estado_alarma' => '1'));;
            $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
        }
        else {
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));

    $app->get('/alertas/desactivar/:id', $adminAuthenticate($app, $settings), function($id) use ($app, $settings) {
        $post = Posts::where('id', '=', $id)->first();

        if($post){
            $redirect = $settings->base_url . '/intranet/alertas/listado';
            Posts::where('id', '=', $id)->update(array('estado_alarma' => '0'));		
            $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
        }
        else {
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));
	
		
	// BORRAR ALERTAS
    $app->get('/alertas/borrar/:id', $adminAuthenticate($app, $settings), function($id) use ($app) {
        $post = Posts::where('id', '=', $id)->first();

        if($post){
            $app->render('intranet_blog_borrar.html', array('post_id' => $id, 'post' => $post));
        }
        else {
            $app->render('intranet_error_404.html');
        }

    })->conditions(array('id' => '\d+'));

    $app->delete('/alertas/borrar/:id', $adminAuthenticate($app, $settings), function($id) use ($app, $settings) {
        $post = Posts::where('id', '=', $id)->first();

        if($post){
            Posts::destroy($id);
            $redirect = $settings->base_url . '/intranet/alertas/listado';
            $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
        }
        else {
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));
	

	// EDITAR ALERTAS
    $app->get('/alertas/editar/:id', $adminAuthenticate($app, $settings), function($id) use ($app) {
        $post = Posts::where('id', '=', $id)->first();

        if($post){
            $postId 			= 	$id;
			$fecha_alerta 		= 	$post->fecha_alerta;
			// Damos formato a la fecha
			$fecha_original 	= 	$post['fecha_alerta'];
			$invert = explode("-",$fecha_original);
			$fecha_alerta = $invert[2]."-".$invert[1]."-".$invert[0];
			// Continuamos con el resto de campos
			$hora_alerta 		= 	$post->hora_alerta;			
			$zonas 				= 	$post->zonas;				
            $resumen			= 	$post->resumen;			
            $detalle 			= 	$post->detalle;
            $nivel_alerta 		= 	$post->nivel_alarma;
            $flash = $app->view()->getData('flash');
            $error = isset($flash['error']) ? $flash['error'] : '';

            $app->render('intranet_blog_editar.html', array(
			'id'			    => $postId, 
			'fecha_alerta'		=> $fecha_alerta, 			
			'hora_alerta' 		=> $hora_alerta, 
			'zonas' 			=> $zonas, 			
			'resumen' 			=> $resumen, 
			'detalle' 			=> $detalle, 
			'nivel_alerta'		=> $nivel_alerta, 			
			'error' 			=> $error));
        }
        else{
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));

	// POST EDITAR UNA ALERTA
    $app->post('/alertas/editar/:id', $adminAuthenticate($app, $settings), function($id) use ($app, $settings) {

		// Extraemos los valores de los campos introducidos en el Formulario
	    $fecha_alerta 		    = 	$app->request->post('campo_fecha_alerta');
		$hora_alerta 		    = 	$app->request->post('campo_hora_alerta');
		$nivel_alerta			= 	$app->request->post('campo_nivel_alerta');
		$zonas_afectadas		= 	$app->request->post('campo_zonas');		
		$resumen 				= 	$app->request->post('campo_resumen');				
        $detalle 				= 	$app->request->post('campo_detalle');

		// Asignamos la fecha
		$updated_at 			= 	date('Y-m-d H:i:s');		
		$user_id 				= 	Users::get_id($_SESSION['admin']);		
		$slug 					= 	$fecha_alerta.$hora_alerta;	


        $post = Posts::where('id', '=', $id)->first();
        if($post){
            if ($fecha_alerta == "") {
                $app->flash('error', 1);
                $app->redirect($settings->base_url . '/intranet/alertas/editar/' . $id);
            }
            if ($hora_alerta == "") {
                $app->flash('error', 2);
                $app->redirect($settings->base_url . '/intranet/alertas/editar/' . $id);
            }

            if ($nivel_alerta == "") {
                $app->flash('error', 3);
                $app->redirect($settings->base_url . '/intranet/alertas/editar/' . $id);
            }
            if ($zonas_afectadas == "") {
                $app->flash('error', 4);
                $app->redirect($settings->base_url . '/intranet/alertas/editar/' . $id);
            }			
            if ($resumen == "") {
                $app->flash('error', 5);
                $app->redirect($settings->base_url . '/intranet/alertas/editar/' . $id);
            }
            if ($detalle == "") {
                $app->flash('error', 6);
                $app->redirect($settings->base_url . '/intranet/alertas/editar/' . $id);
            }			
			
            $redirect = $settings->base_url . '/intranet/alertas/listado';

			// Invertimos la fecha para adaptarla al formato válido.
			$fecha = $fecha_alerta;
			$invert = explode("-",$fecha);
			$fecha_invertida = $invert[2]."-".$invert[1]."-".$invert[0];

			Posts::where('id', '=', $id)->update(array(
			'fecha_alerta'  	 => 	$fecha_invertida, 
			'hora_alerta'  	     => 	$hora_alerta,
			'zonas'				 => 	$zonas_afectadas, 
			'resumen'		 	 => 	$resumen, 		
			'detalle' 			 => 	$detalle,		
			'nivel_alarma' 		 => 	$nivel_alerta,		
			'slug'				 => 	$slug,
			'user_id' 		 	 => 	$user_id,		
			'updated_at' 		 => 	$updated_at					
			
			
			));

            $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
        }
        else {
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));	



	// ********************************************** //
	// *** FUNCIONALIDADES DE GESTIÓN DE USUARIOS *** //
	// ********************************************** //

	// LISTADO DE USUARIOS
    $app->get('/usuarios/listado', $adminAuthenticate($app, $settings), function() use ($app) {
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
        $app->render('intranet_usuarios_listado.html', array('usuarios' => $arr));
    });

	// ALTA DE USUARIO
    $app->get('/usuarios/nuevo', $adminAuthenticate($app, $settings), function() use ($app) {
        $flash = $app->view()->getData('flash');
        $error = isset($flash['error']) ? $flash['error'] : '';

		
        $app->render('intranet_usuarios_nuevo.html', array('error' => $error));
    });

    $app->post('/usuarios/nuevo', $adminAuthenticate($app, $settings), function() use ($app, $settings) {
	
		// Extraemos los valores de los campos introducidos en el Formulario
	    $nombre 		= 	$app->request->post('campo_nombre');
		$apellidos 		= 	$app->request->post('campo_apellidos');
		$email			= 	$app->request->post('campo_email');
		$telefono		= 	$app->request->post('campo_telefono');
		$zonas			= 	$app->request->post('campo_zonas');		
		$password		= 	hash('sha512', $app->request->post('campo_password'));				

		// Asignamos otros campos adicionales
        $creacion 		= 	date('Y-m-d H:i:s');
        $tipo_usuario 	= 	"E";		
        $estado 		= 	"1";
		$api_token 		= 	str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789".uniqid());
		
		// Control de Errores
        $redirect = $app->request->post('redirect');

        if ($nombre == "") {
            $app->flash('error', 1);
            $app->redirect($settings->base_url . '/intranet/usuarios/nuevo');
        }
        if ($apellidos == "") {
            $app->flash('error', 2);
            $app->redirect($settings->base_url . '/intranet/usuarios/nuevo');
        }
        if ($email == "") {
            $app->flash('error', 3);
            $app->redirect($settings->base_url . '/intranet/usuarios/nuevo');
        }
        if ($telefono == "") {
            $app->flash('error', 4);
            $app->redirect($settings->base_url . '/intranet/usuarios/nuevo');
        }
        if ($zonas == "") {
            $app->flash('error', 5);
            $app->redirect($settings->base_url . '/intranet/usuarios/nuevo');
        }
        if ($password == "") {
            $app->flash('error', 6);
            $app->redirect($settings->base_url . '/intranet/usuarios/nuevo');
        }		
		
		// Insertamos los datos en la tabla
        Users::insert(array(
		'nombre'  		=> 	$nombre, 
		'apellidos'  	=> 	$apellidos,
		'email'			=> 	$email, 
		'telefono'		=> 	$telefono, 		
		'zona' 			=> 	$zonas,		
		'password' 		=> 	$password,		
		'estado' 		=> 	$estado,
		'tipo_usuario' 	=>  $tipo_usuario, 
		'api_token' 	=>  $api_token, 		
		'creacion' 		=> 	$creacion		
		));

		$redirect = $settings->base_url . '/intranet/usuarios/listado';
        $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
    });

	
		
	// BORRAR USUARIOS
    $app->get('/usuarios/borrar/:id', $adminAuthenticate($app, $settings), function($id) use ($app) {
        $usuario = Users::where('id', '=', $id)->first();

        if($usuario){
            $app->render('intranet_usuarios_borrar.html', array('usuario_id' => $id, 'usuario' => $usuario));
        }
        else {
            $app->render('intranet_error_404.html');
        }

    })->conditions(array('id' => '\d+'));

    $app->delete('/usuarios/borrar/:id', $adminAuthenticate($app, $settings), function($id) use ($app, $settings) {
        $usuario = Users::where('id', '=', $id)->first();

        if($usuario){
            Users::destroy($id);
            $redirect = $settings->base_url . '/intranet/usuarios/listado';
            $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
        }
        else {
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));
	

	// EDITAR USUARIOS
    $app->get('/usuarios/editar/:id', $adminAuthenticate($app, $settings), function($id) use ($app) {
        $usuario = Users::where('id', '=', $id)->first();

        if($usuario){
            $id 				= 	$id;
			$nombre 			= 	$usuario->nombre;
			$apellidos 			= 	$usuario->apellidos;			
			$email 				= 	$usuario->email;				
            $telefono			= 	$usuario->telefono;			
            $zona 				= 	$usuario->zona;
            $passowrd 			= 	$usuario->passowrd;
			
            $flash = $app->view()->getData('flash');
            $error = isset($flash['error']) ? $flash['error'] : '';

            $app->render('intranet_usuarios_editar.html', array(
			'id'				=> $id, 
			'nombre'			=> $nombre, 			
			'apellidos' 		=> $apellidos, 
			'email' 			=> $email, 			
			'telefono' 			=> $telefono, 
			'zona' 				=> $zona, 
			'error' 			=> $error));
        }
        else{
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));

    $app->post('/usuarios/editar/:id', $adminAuthenticate($app, $settings), function($id) use ($app, $settings) {

		// Extraemos los valores de los campos introducidos en el Formulario
	    $nombre 		= 	$app->request->post('campo_nombre');
		$apellidos 		= 	$app->request->post('campo_apellidos');
		$email			= 	$app->request->post('campo_email');
		$telefono		= 	$app->request->post('campo_telefono');
		$zonas			= 	$app->request->post('campo_zonas');		
		$password		= 	hash('sha512', $app->request->post('campo_password'));				
		// Asignamos otros campos adicionales
        $updated_at 		= 	date('Y-m-d H:i:s');
		
        $usuario = Users::where('id', '=', $id)->first();
        if($usuario){
            if ($nombre == "") {
                $app->flash('error', 1);
                $app->redirect($settings->base_url . '/intranet/usuarios/editar/' . $id);
            }
            if ($apellidos == "") {
                $app->flash('error', 2);
                $app->redirect($settings->base_url . '/intranet/usuarios/editar/' . $id);
            }
            if ($email == "") {
                $app->flash('error', 3);
                $app->redirect($settings->base_url . '/intranet/usuarios/editar/' . $id);
            }
            if ($telefono == "") {
                $app->flash('error', 4);
                $app->redirect($settings->base_url . '/intranet/usuarios/editar/' . $id);
            }			
            if ($zonas == "") {
                $app->flash('error', 5);
                $app->redirect($settings->base_url . '/intranet/usuarios/editar/' . $id);
            }
			
			
            $redirect = $settings->base_url . '/intranet/usuarios/listado';

			// Modificamos los datos
			if( !empty($password) ) {
				// Si hemos puesto una nueva contraseña
				Users::where('id', '=', $id)->update(array(
				'nombre'  	 	=> 	$nombre, 
				'apellidos'		=> 	$apellidos, 
				'email'		 	=> 	$email, 	
				'telefono'		=> 	$telefono, 					
				'zona' 			=> 	$zonas,		
				'password' 		=> 	$password,		
				'updated_at' 	=> 	$updated_at					
				));
			} else {
				// Si no hemos puesto una nueva contraseña y hemos dejado el campo en blanco
				Users::where('id', '=', $id)->update(array(
				'nombre'  	 	=> 	$nombre, 
				'apellidos'		=> 	$apellidos, 
				'email'		 	=> 	$email, 					
				'telefono'		=> 	$telefono, 					
				'zona' 			=> 	$zonas,		
				'updated_at' 	=> 	$updated_at					
				));	
			}	
	
            $app->render('intranet_info_correcto.html', array('redirect' => $redirect));
        }
        else {
            $app->render('intranet_error_404.html');
        }
    })->conditions(array('id' => '\d+'));	
	
	
	
$app->get('/error-permisos', function () use ($app) {
    $app->render('error-permisos-admin.html');
});
	
});

