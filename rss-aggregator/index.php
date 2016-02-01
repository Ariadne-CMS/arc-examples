<?php
    require __DIR__ . '/../vendor/autoload.php';

    use \arc\html as h;

    run();

    function run() {
        $client = \arc\cache::proxy( \arc\http::client(), function($params) {
             return ( \arc\http\headers::parseCacheTime( $params['target']->responseHeaders ) );
        });

        $feeds = handleFeeds(__DIR__ . '/../data/feeds.json', $client);

        $aggregator = aggregator($client);

        echo h::doctype()
        .h::html(
            h::head(
                h::title('RSS Aggregator'),
                h::link(['href' => '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css', 'rel' => 'stylesheet']),
                h::link(['href' => 'cards.css', 'rel' => 'stylesheet']),
                h::script(['src' => '//code.jquery.com/jquery-1.12.0.min.js']),
                h::script(['src' => 'cards.js'])
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

    }

    function handleFeeds( $path, $client ) {

        $feeds = json_decode(file_get_contents($path), true);
        $newFeed = \arc\hash::get('/newFeed/', $_POST);
        if ( isset($newFeed) ) {
            $feeds[] = $newFeed;
        }
        $removeFeed = \arc\hash::get('/removeFeed/', $_POST);
        if ( isset($removeFeed) ) {
            unset($feeds[ $removeFeed ]);
        }
        if ( isset($removeFeed) || isset($newFeed) ) {
            file_put_contents( $path, json_encode($feeds));
        }
        return $feeds;
    }

    function aggregator($client) {
        $aggregator = \arc\lambda::prototype();

        $aggregator->renderItem = function($item)
        {
            if ( $item->enclosure['url'] ) {
                $img = h::a( ['href' => $item->link->nodeValue], h::img( [ 'src' => $item->enclosure['url'], 'alt' => '']));
            } else {
                $img = '';
            }
            $pubDate = h::time( relativeTime( strtotime( current( $item->xpath('.//pubDate|.//dc:date') ) ) ) );
            return h::article(
                ['class' => 'white-panel'],
                $img,
                h::h2( h::a( [ 'href' => $item->link->nodeValue ], $item->title->nodeValue ) ),
                $pubDate,
                h::p( [ 'class' => 'description' ], h::raw(strip_tags($item->description->nodeValue)) )
            );
        };

        $aggregator->renderItems = function($items)
        {
            $itemsString = '';
            if ( count($items) ) {
                foreach ($items as $item ) {
                     $itemsString .= $this->renderItem($item);   
                }
            } else {
                $itemsString = h::div(['class' => 'warning'], 'No articles in this feed.');
            }
            return h::raw($itemsString);
        };

        $aggregator->renderFeeds = function($feeds)
        {
            return implode( array_map( function($feed, $index) {
                return h::div(['class' => 'feed'], 
                    h::h2( $feed->channel->title->nodeValue ),
                    h::form(['class' => 'remove', 'method' => 'POST'],
                        h::button(['name' => 'removeFeed', 'value' => $index], 'Remove' )
                    ),
                    h::a( ['href' => $feed->channel->link->nodeValue ], $feed->channel->link->nodeValue )
                );
            }, $feeds, array_keys($feeds) ) );
        };

        $aggregator->getPubDate = function($item) {
            return strtotime( current( $item->xpath('.//pubDate|.//dc:date') ) );
        };

        $aggregator->getItems = function($feeds)
        {
            $allitems = call_user_func_array(
                'array_merge',
                array_map(
                    function($rss) {
                        return array_map(
                            function($item) use ($rss) {
                                $item->appendChild( $rss->channel->cloneNode() );
                                return $item;
                            },
                            $rss->getElementsByTagName('item')
                        );
                    },
                    $feeds
                )
            );
            usort( $allitems, function($a, $b) {
                return $this->getPubDate($a) < $this->getPubDate($b);
            });
            return $allitems;
        };

        $aggregator->getFeed = function($url) use($client)
        {
            $feed = $client->get($url);
            try {
                $rss = \arc\xml::parse($feed);
            } catch( \Exception $e ) {
                $rss = \arc\html::parse($feed);
            }
            $rss->registerXPathNamespace('dc','http://purl.org/dc/elements/1.1/');
            return $rss;
        };

        $aggregator->render = function($feeds)
        {
            $feeds = array_map(
                function($feed) {
                    return $this->getFeed($feed);
                },
                $feeds
            );
            $items = $this->getItems($feeds);
            return h::section(
                ['class' => 'items'],
                h::raw($this->renderItems($items))
            )
            .h::section(
                ['class' => 'feeds'],
                h::raw($this->renderFeeds($feeds))
            );
        };
		return $aggregator;
	}


    function relativeTime($time)
    {   
        $delta = time() - $time;
        $epochs = [
            'second' => 1,
            'minute' => 60,
            'hour'   => 3600,
            'day'    => 86400,
            'week'   => 604800,
            'month'  => 2630880,
            'year'   => 31570560,
            'decade' => 315705600
        ];
        do {
            end( $epochs );
            $epoch = key( $epochs );
            $epochDelta  = array_pop( $epochs );
            if ( $delta > $epochDelta ) {
                $amount = floor( $delta / $epochDelta );
                if ( $amount > 1 ) {
                    return $amount . ' ' . $epoch .'s ago';
                } else {
                    return ( $epoch == 'hour' ? 'an ' : 'a ' ) . $epoch . ' ago';
                }
            }
        } while( count($epochs));
        return '';
    };

