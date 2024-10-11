<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    protected $js_files = [];
    protected $css_files = [];

    public function getFunctions()
    {
        return [
            new TwigFunction('getStyles', [$this, 'getStyles']),
            new TwigFunction('getScripts', [$this, 'getScripts']),
            new TwigFunction('addStyle', [$this, 'addStyle']),
            new TwigFunction('addScript', [$this, 'addScript']),
        ];
    }

    public function getStyles()
    {
        return $this->css_files;
    }

    public function getScripts()
    {
        return $this->js_files;
    }

    public function addStyle($file)
    {
        if (!in_array($file, $this->css_files)) {
            $this->css_files[] = $file;
        }
    }

    public function addScript($file)
    {
        if (!in_array($file, $this->js_files)) {
            $this->js_files[] = $file;
        }
    }
}
