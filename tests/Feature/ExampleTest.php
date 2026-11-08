<?php

test('redirects the application root to the dashboard', function () {
    $this->get('/')->assertRedirect('/dashboard');
});
