<?php

test('the application redirects guests to the operational login', function () {
    $this->get('/')->assertRedirect(route('login'));
});
