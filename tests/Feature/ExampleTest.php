<?php

use Inertia\Testing\AssertableInertia as Assert;

test('welcome page is rendered', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome'),
    );
});
