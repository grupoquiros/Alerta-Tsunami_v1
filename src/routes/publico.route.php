<?php
/* **********************
   * Rutas zona pública *
   **********************
*/

	// Login
	$app->get('/login', $isLogged($app, $settings), function() use ($app) {
        $flash = $app->view()->getData('flash');
        $error = isset($flash['error']) ? $flash['error'] : '';

        $app->render('publico_login.html', array (
		'error' => $error,
		"titulo" 			=> 	'Alerta Tsunami',
		"descripcion" 		=> 	'TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo',
		"keywords" 			=> 	'tsunami, tsunamis, maremoto, maremotos',		
		"autor" 			=> 	'Alerta Tsunami'				
		));
    });

	// Login
	$app->post('/login', function() use ($app, $settings) {
        $username = $app->request->post('form-username');
        $password = hash('sha512', $app->request->post('form-password'));
        $user = Users::whereRaw('email = ? AND password = ?', array($username, $password))->get();

		// Extrae el tipo de Usuario
		
		if ($user->count() != 0 ) {
			$t_user = Users::where('email', '=', $username)->first();
			$tipoUsuario = $t_user->tipo_usuario;
			$id_empresa =  $t_user->id_empresa;			
			
			$_SESSION['id_empresa_activa'] = $id_empresa;
		}
		
        if ($user->count() != 0 AND ($tipoUsuario == 'P' OR $tipoUsuario == 'E')) {
            $_SESSION['user'] = $username;
            $app->redirect($settings->base_url . '/usuarios');

		} elseif ($user->count() != 0 AND $tipoUsuario == 'T') {
		    $_SESSION['admin'] = $username;
            $app->redirect($settings->base_url . '/intranet');
			
        } else {
            $app->flash('error', 1);
            $app->redirect($settings->base_url . '/login');
        }
    });

	// Home
	$app->get('/', function($page = 1) use ($app, $settings) {

	$p = Posts::where('estado_alarma', '1')->count();	
    $pages = ceil($p / $settings->post_per_page);
    if ($page > $pages) $app->pass();

    $posts = Posts::orderBy('fecha_alerta', 'desc') -> where ('estado_alarma', '1') ->skip($settings->post_per_page * ($page - 1))->take($settings->post_per_page)->get();
    $arr = array(); //Posts
    foreach ($posts as $post) {
        if ($post['estado_alarma'] == '1') {
            $post['author'] = Users::get_author($post['user_id']);

			// Damos formato a la fecha
			$fecha_original = $post['fecha_alerta'];
			$invert = explode("-",$fecha_original);
			$post['date'] = $invert[2]."-".$invert[1]."-".$invert[0];			
			
            $post['url'] = $app->request->getUrl() . $app->request->getPath() . 'alertas/' . $post['id'];
            if ($settings->truncate == 'true') {
                $text = truncate_to_n_words($post['text'], 70, $post['url']);
                $post['text'] = $app->markdown->parse($text);
            } else {
                $post['text'] = $app->markdown->parse($post['text']);
            }

            $post['count'] = Posts::find($post['id'])->comments->count();
            $arr[] = $post;

        }
    }
	
    $app->render('publico_home.html', array('posts' => $arr, 'pages' => $pages, 'page' => $page, 'contador_alertas' => $p,
		"titulo" 			=> 	'Alerta Tsunami',
		"descripcion" 		=> 	'TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo',
		"keywords" 			=> 	'tsunami, tsunamis, maremoto, maremotos',		
		"autor" 			=> 	'Alerta Tsunami',
		"fecha_original"	=>  $fecha_original
	));
})->conditions(array('page' => '\d+'));

// Home
$app->get('/', function () use ($app) {
    $app->render('publico_home.html', array (
		"titulo" 			=> 	'Alerta Tsunami',
		"descripcion" 		=> 	'TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo',
		"keywords" 			=> 	'tsunami, tsunamis, maremoto, maremotos',		
		"autor" 			=> 	'Alerta Tsunami'	
	));
});	

// El Proyecto
$app->get('/el-proyecto', function () use ($app) {
    $app->render('publico_proyecto.html', array (
		"titulo" 			=> 	'Alerta Tsunami',
		"descripcion" 		=> 	'TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo',
		"keywords" 			=> 	'tsunami, tsunamis, maremoto, maremotos',		
		"autor" 			=> 	'Alerta Tsunami'	
	));
});


// Avisos y Política 
$app->get('/politica-de-cookies', function () use ($app) {
    $app->render('publico_politica_cookies.html', array (
		"titulo" 			=> 	'Política de Cookies',
		"descripcion" 		=> 	'Descripción de la política de cookies seguida por Alerta Tsunami.',
		"keywords" 			=> 	'cookies, politica de cookies',		
		"autor" 			=> 	'Alerta Tsunami'	
	));
});	


$app->get('/politica-de-privacidad', function () use ($app) {
    $app->render('publico_politica_privacidad.html', array (
		"titulo" 			=> 	'Política de Privacidad',
		"descripcion" 		=> 	'Política de Privacidad correspondiente al sitio www.alertatsunami.com de Alerta Tsunami.',
		"keywords" 			=> 	'politica privacidad',		
		"autor" 			=> 	'Alerta Tsunami'	
	));
});	


$app->get('/error-permisos', function () use ($app) {
    $app->render('publico_error_permisos.html');
});	




/* Registro de usuarios en el sistema de Alertas */
$app->get('/registro', function() use ($app) {
        $flash = $app->view()->getData('flash');
        $error = isset($flash['error']) ? $flash['error'] : '';

        $app->render('publico_registro_usuarios.html', array('error' => $error));
    });

	$app->post('/registro', function() use ($app, $settings) {
        $nombre 		=	$app->request->post('campo_nombre');
        $apellidos 		= 	$app->request->post('campo_apellidos');		
		$email 			= 	$app->request->post('campo_email');
		$telefono 		=	$app->request->post('campo_telefono');	
		$zona 			=	$app->request->post('campo_zona');		
        $password		= 	hash('sha512', $app->request->post('campo_password'));
        
        $creacion 		= 	date('Y-m-d H:i:s');
        $tipo_usuario 	= 	"E";		
        $estado 		= 	"1";
		$api_token 		= 	str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789".uniqid());
		

		// Control de errores
        if($nombre == "") {
            $app->flash('error', 1);
            $app->redirect($settings->base_url . '/registro');
        }
        if($apellidos == "") {
            $app->flash('error', 2);
            $app->redirect($settings->base_url . '/registro');
        }		
        if($email == "" OR !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $app->flash('error', 3);
            $app->redirect($settings->base_url . 'registro');
        }		
        if($telefono == "") {
            $app->flash('error', 4);
            $app->redirect($settings->base_url . '/registro');
        }	
        if($zona == "") {
            $app->flash('error', 5);
            $app->redirect($settings->base_url . '/registro');
        }	
        if($password == "") {
            $app->flash('error', 6);
            $app->redirect($settings->base_url . '/registro');
        }

        $redirect = $settings->base_url . '/';

		// Salvar Datos
        Users::insert(array(
		'nombre' 		=> $nombre, 
		'apellidos' 	=> $apellidos, 
		'email' 		=> $email, 
		'telefono' 		=> $telefono, 		
		'zona' 			=> $zona, 		
		'password' 		=> $password, 
		'creacion' 		=> $creacion, 
		'tipo_usuario' 	=> $tipo_usuario, 
		'api_token' 	=> $api_token, 
		'estado' 		=> $estado
		
		));


		// Envío SMS mediante llamada a la API SMSup
		$telefono_sms = "34".$telefono;		
		$request = '{
		  "api_key":"a3699d42cfde231a580b6e2f9ef84840",
		  "fake":"1",
		  "messages":[ 
		  {
				"from":"TSUNAMI",
				"to":"'.$telefono_sms.'",
				"text":"Su registro en Alerta Tsunami se ha realizado correctamente."
		  }
		  ]
		}';		


		
		$headers = array('Content-Type: application/json');
		  $ch = curl_init('https://api.gateway360.com/api/3.0/sms/send');
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		  curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		  
		  $result = curl_exec($ch);
		  
		if (curl_errno($ch) != 0 ){
			die("curl error: ".curl_errno($ch));
		  
            $app->flash('error', 9);
            $app->redirect($settings->base_url . '/registro');
		}
		// Fin del envío SMS
		
		$app->flash('error', 0);
        $app->redirect($settings->base_url . '/registro');			
    });	
	

/* Formulario de contacto */
$app->get('/contacto', function() use ($app) {
        $flash = $app->view()->getData('flash');
        $error = isset($flash['error']) ? $flash['error'] : '';

        $app->render('publico_contacto.html', array(
		'error' => $error,
		"titulo" 			=> 	'Alerta Tsunami',
		"descripcion" 		=> 	'TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo',
		"keywords" 			=> 	'tsunami, tsunamis, maremoto, maremotos',		
		"autor" 			=> 	'Alerta Tsunami'	
		));
    });

$app->post('/contacto', function() use ($app, $settings) {
 	    // Recuperamos los campos del Formulario
		$nombre 	= $app->request->post('nombre');
        $email 		= $app->request->post('email');
        $telefono 	= $app->request->post('telefono');
        $mensaje 	= $app->request->post('mensaje');		
        $check 		= $app->request->post('check');				

		// Control de Errores
        if($nombre == "") {
            $app->flash('error', 1);
            $app->redirect($settings->base_url . '/contacto');
        }
        if($email == "" OR !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $app->flash('error', 2);
            $app->redirect($settings->base_url . '/contacto');
        }
        if($telefono == "") {
            $app->flash('error', 3);
            $app->redirect($settings->base_url . '/contacto');
        }
        if($mensaje == "") {
            $app->flash('error', 4);
            $app->redirect($settings->base_url . '/contacto');
        }		
        if($check == "") {
            $app->flash('error', 5);
            $app->redirect($settings->base_url . '/contacto');
        }			

		// Inicio bloque control de Recatcha
		$recaptcha 	= $app->request->post('g-recaptcha-response');
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$data = array(
			'secret' => '6LcW7agUAAAAAM_oQidsHvRPN1tCshz6pRtz_I7F',
			'response' => $recaptcha
		);
		$query = http_build_query($data);
		$options = array(
			'http' => array (
				'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 
				"Content-Length: ".strlen($query)."\r\n".
				"User-Agent:MyAgent/1.0\r\n",
				'method' => 'POST',
				'content' => $query
			)
		);
		$context  = stream_context_create($options);
		$verify = file_get_contents($url, false, $context);
		$captcha_success = json_decode($verify);
		if ($captcha_success->success) {
			// No eres un robot, continuamos con el envío del email
		} else {
            $app->flash('error', 6);
            $app->redirect($settings->base_url . '/contacto');
		}
		// Fin bloque control de Recatcha
		
        $redirect = $settings->base_url . '/contacto';

		// Parámetros del Correo
		$destinatario 		= 'miguel@quiros.com';          
		$remitente          = 'Formulario Web Alerta Tsunami';      
		$mailRemitente      = 'info@alertatsunami.com';
		$asunto 			= "Formulario desde Alerta Tsunami";
		$cuerpoMensaje 	    ='
		<h3>Formulario Contacto Alerta Tsunami</h3>
		<p>Se ha solicitado información desde la página web de Alerta Tsunami.</p>
		<br>
		<p>Nombre: 		'.$nombre.'   </p>
		<p>Email: 		'.$email.' 	  </p>											
		<p>Teléfono: 	'.$telefono.' </p>	
		<p>Mensaje: 	'.$mensaje.'  </p>		
		<p>Acepta LOPD:	'.$check.'  </p>				
		<br>		
		';
	
		// Establecer cabeceras para la funcion mail()
		$cabeceras  = "MIME-Version: 1.0\r\n";
		$cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
		$cabeceras .= "From: ".$remitente." <".$mailRemitente.">";

		// Envío del Correo
		mail($destinatario,$asunto,$cuerpoMensaje,$cabeceras);

		$app->flash('error', 0);
        $app->redirect($settings->base_url . '/contacto');			
	
    });
	
// Status: Muestra pantalla del estado de la Alerta
$app->get('/status', function($page = 1) use ($app, $settings) {

	$p = Posts::where('estado_alarma', '1')->count();	
    $posts = Posts::orderBy('fecha_alerta', 'desc') -> where ('estado_alarma', '1') ->skip($settings->post_per_page * ($page - 1))->take($settings->post_per_page)->get();
    $arr = array(); //Posts
    foreach ($posts as $post) {
        if ($post['estado_alarma'] == '1') {

			// Damos formato a la fecha
			$fecha_original = $post['fecha_alerta'];
			$invert = explode("-",$fecha_original);
			$post['date'] = $invert[2]."-".$invert[1]."-".$invert[0];			
            $arr[] = $post;
        }
    }
	
    $app->render('status.html', array('posts' => $arr, 'contador_alertas' => $p,
		"titulo" 			=> 	'Alerta Tsunami',
		"descripcion" 		=> 	'TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo',
		"keywords" 			=> 	'tsunami, tsunamis, maremoto, maremotos',		
		"autor" 			=> 	'Alerta Tsunami'

	));
});	

	
	
	

$app->notFound(function(Exception $e = NULL ) use ($app) {
		$app->render('publico_error_404.html'); 
});	



	