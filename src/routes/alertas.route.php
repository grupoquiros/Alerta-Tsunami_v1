<?php
// **********************
// *** CÓDIGO ALERTAS ***
// **********************


// Lista Alertas
$app->get('/alertas(/)(:page)', function($page = 1) use ($app, $settings) {
    // $p = Posts::count();
	$p = Posts::where('active', 'true')->count();	
    $pages = ceil($p / $settings->post_per_page);
    if ($page > $pages) $app->pass();

    $posts = Posts::orderBy('fecha_alerta', 'desc') -> where ('active', 'true') ->skip($settings->post_per_page * ($page - 1))->take($settings->post_per_page)->get();
    $arr = array(); //Posts
    foreach ($posts as $post) {

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

    $app->render('publico_blog_listado.html', array('posts' => $arr, 'pages' => $pages, 'page' => $page,
		"titulo" 			=> 	'Alerta Tsunami (Página ' .$page. ')',
		"descripcion" 		=> 	'TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo',
		"keywords" 			=> 	'tsunami, tsunamis, maremoto, maremotos',		
		"autor" 			=> 	'Alerta Tsunami',
		"fecha_original"	=>  $fecha_original
	));
})->conditions(array('page' => '\d+'));


// Detalle de una Alerta
$app->get('/:slug', function($slug) use ($app) {
	$post = Posts::where('slug', '=', $slug)->first();
    if ($post <> '' and $post['active'] == 'true') {
        $flash = $app->view()->getData('flash');
        $error = isset($flash['error']) ? $flash['error'] : '';

		// Damos formato a la fecha
		$fecha_original = $post['fecha_alerta'];
		$invert = explode("-",$fecha_original);
		$post['date'] = $invert[2]."-".$invert[1]."-".$invert[0];			
		
        $post->text = $app->markdown->parse($post->text);
        $post->count = Posts::find($post->id)->comments->count();

        $redirect = $app->request->getUrl() . $app->request->getPath();

			// Preparamos el contenido de las etiquetas metas
			$titulo 		= "Alerta Tsunami";
			$descripcion 	= "TFG UNIR, cuyo objetivo radica en que una vez se tiene la certeza que la ola se acerca a las costas, Alerta Tsunami permite avisar directamente a la población, para que evacuen las zonas de riesgo";
			$keywords       = "tsunami, tsunamis, maremoto, maremotos";
			$autor       	= "Alerta Tsunami";
			
			
        $app->render('publico_blog_detalle.html', array('post' => $post, 'error' => $error, 'redirect' => $redirect,
			"titulo" 			=> 	$titulo,
			"descripcion" 		=> 	$descripcion,
			"keywords" 			=> 	$keywords,		
			"autor" 			=> 	$autor,
			"fecha_original"	=>  $fecha_original
		));
    }
    else {
		// Si no hay una alerta continua la búsqueda aplicando pass
        // $app->render('publico_error_404.html');
		$app->pass();
    }
})->conditions(array('page' => '\d+'));



// FIN
