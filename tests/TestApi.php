<?php

namespace glook\apist\tests;

use glook\apist\Apist;

class TestApi extends Apist
{
    protected ?string $baseUri = 'http://example.com';

    public function index()
    {
        return $this->get('/', [
            'title' => Apist::filter('.page_head .title'),
            'copyright' => Apist::filter('.copyright .about a')->first()->attr('href'),
            'posts' => Apist::filter('.posts .post')->each(function () {
                return [
                    'title' => Apist::filter('h1.title a')->text(),
                ];
            }),
        ]);
    }

    public function element_not_found()
    {
        return $this->get('/', [
            'title' => Apist::filter('.page_header'),
        ]);
    }

    public function non_array_blueprint()
    {
        return $this->get('/', Apist::filter('.page_head .title'));
    }

    public function plain_return()
    {
        return $this->get('/');
    }

    public function parseContent($content, $blueprint)
    {
        return $this->parse($content, $blueprint);
    }

    public function doPost($url, $blueprint = null, $options = [])
    {
        return $this->post($url, $blueprint, $options);
    }

    public function customMethod($node)
    {
        return $node->text() . '_custom';
    }
}
