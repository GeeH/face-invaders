<?php

it('responds to the health check', function () {
    $this->get('/up')->assertOk();
});

it('renders the home page', function () {
    $this->get('/')->assertOk();
});
