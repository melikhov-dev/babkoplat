<?php
namespace Flint\Template;

interface TemplateEngineInterface
{
    function render($template, array $data = array());
    function cache();
}
