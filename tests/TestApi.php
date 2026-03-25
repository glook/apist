<?php

namespace glook\apist\tests;

use glook\apist\Apist;

class TestApi extends Apist
{
    protected ?string $baseUrl = 'http://example.com';

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

    public function setRequestOptions(array $options): void
    {
        $this->requestOptions = $options;
    }

    public function doGet($url, $blueprint = null, $options = [])
    {
        return $this->get($url, $blueprint, $options);
    }

    public function doPut($url, $blueprint = null, $options = [])
    {
        return $this->put($url, $blueprint, $options);
    }

    public function doPatch($url, $blueprint = null, $options = [])
    {
        return $this->patch($url, $blueprint, $options);
    }

    public function doDelete($url, $blueprint = null, $options = [])
    {
        return $this->delete($url, $blueprint, $options);
    }

    public function doHead($url, $blueprint = null, $options = [])
    {
        return $this->head($url, $blueprint, $options);
    }
}
