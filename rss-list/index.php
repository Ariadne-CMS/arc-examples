<?php

    require __DIR__ . '/../vendor/autoload.php';

    use \arc\html as h;

    $client = \arc\cache::proxy( \arc\http::client(), function($params) {
        return ( \arc\http\headers::parseCacheTime( $params['target']->responseHeaders ) );
    });

    $feeds = json_decode(file_get_contents('../data/feeds.json'),true) ?: [];

    $aggregator = \arc\lambda::prototype([
		'renderItem' => function($item) {
			return h::article(
                h::h3( h::a( [ 'href' => $item->link->nodeValue ], $item->title->nodeValue ) ),
                h::div( [ 'class' => 'body' ], $item->description->nodeValue )
            );
		},
		'renderItems' => function($items) {
            $itemsString = '';
            if ( count($items) ) {
                foreach ($items as $item ) {
                 	$itemsString .= $this->renderItem($item);   
                }
            } else {
                $itemsString = h::div(['class' => 'warning'], 'No articles in this feed.');
            }
			return h::raw($itemsString);
		},
		'renderFeed' => function($title, $link, $index, $items) {
			return h::section(['class' => 'feed'], 
                h::header(
                    h::h2( $title ),
                    h::form(['class' => 'remove', 'method' => 'POST'],
                        h::button(['name' => 'removeFeed', 'value' => $index], 'Remove' )
                    )
                ),
                h::div(['class' => 'article-body'], $this->renderItems($items) ),
                h::footer(
                    h::a( ['href' => $link], $link )
                )
            );
		},
        'getFeed' => function($url, $index) use ($client) {
            $feed = $client->get($url);

            try {
                $rss = \arc\xml::parse($feed);
            } catch( \Exception $e ) {
                $rss = \arc\html::parse($feed);
            }

            $items = $rss->find('item');
            return $this->renderFeed(
				$rss->channel->title->nodeValue,
				$rss->channel->link->nodeValue,
				$index,
				$items
			);
		},
		'render' => function($feeds) {
            return implode(
                array_map(
                    function($feed, $index) {
                        return $this->getFeed($feed, $index);
                    },
                    $feeds,
                    array_keys($feeds)
                )
            );
        }
	]);

    $newFeed = \arc\hash::get('/newFeed/', $_POST);
    if ( isset($newFeed) ) {
        $feeds[] = $newFeed;
    }
    $removeFeed = \arc\hash::get('/removeFeed/', $_POST);
    if ( isset($removeFeed) ) {
        unset($feeds[ $removeFeed ]);
    }
    if ( isset($removeFeed) || isset($newFeed) ) {
        file_put_contents('data/feeds.json', json_encode($feeds,true));
    }

    echo h::doctype()
    .h::html(
        h::head(
            h::title('RSS Aggregator'),
            h::link(['href' => 'list.css', 'rel' => 'stylesheet'])
        ),
        h::body(
            h::raw($aggregator->render($feeds)),
            h::footer(
                h::form(
                    ['method' => 'POST'],
                    h::input(['name' => 'newFeed']),
                    h::button(['ype' => 'submit'], 'Add feed')
                )
            )
        )
    );
