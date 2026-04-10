<?php

namespace PressbooksBorges\Controllers;

class SearchResultsController extends BaseController
{
    public function render(): void
    {
        echo $this->renderView('search-results');
    }
}
