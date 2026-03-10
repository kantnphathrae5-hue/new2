<?php

declare(strict_types=1);

function renderView(string $template, array $data = []):void
{
    include TEMPLATES_DIR . '/' . $template . '.php';
}   
?>