<?php
	require __DIR__ . '/../vendor/autoload.php';

	use \arc\html as h;
	use \arc\xml as x;

	$client = \arc\cache::proxy( \arc\http::client(), function($params) {
        return ( \arc\http\headers::parseCacheTime( $params['target']->responseHeaders ) );
    });

    $feed = $client->get('https://www.nasa.gov/rss/dyn/breaking_news.rss');
    
	try {
	    $rss = x::parse($feed);
	} catch( \Exception $e ) {
		$rss = h::parse($feed);
	}

	$items = $rss->find('item');
	foreach ($items as $item ) {
		echo h::article(
			h::h2( h::a( [ 'href' => $item->link->nodeValue ], $item->title->nodeValue ) ),
			h::div( [ 'class' => 'body' ], $item->description->nodeValue )
		);
	}
