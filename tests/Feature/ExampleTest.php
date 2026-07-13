<?php

use Inertia\Testing\AssertableInertia as Assert;

test('welcome page is rendered', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome'),
    );
});

test('welcome page includes social sharing metadata in the initial response', function () {
    $response = $this->get(route('home'));
    $socialTitle = config('app.name').' — Playful private invitations';
    $socialDescription = 'Create private invitations with playful challenges, share one link, and reveal the details only after guests solve them.';

    $response
        ->assertOk()
        ->assertSee('<meta name="description" content="'.$socialDescription.'">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:title" content="'.$socialTitle.'">', false)
        ->assertSee('<meta property="og:description" content="'.$socialDescription.'">', false)
        ->assertSee('<meta property="og:url" content="'.route('home').'">', false)
        ->assertSee('<meta property="og:image" content="'.asset('logo.png').'">', false)
        ->assertSee('<meta property="og:image:alt" content="'.config('app.name').' duck logo">', false)
        ->assertSee('<meta name="twitter:card" content="summary">', false)
        ->assertSee('<meta name="twitter:title" content="'.$socialTitle.'">', false)
        ->assertSee('<meta name="twitter:description" content="'.$socialDescription.'">', false)
        ->assertSee('<meta name="twitter:image" content="'.asset('logo.png').'">', false);
});
