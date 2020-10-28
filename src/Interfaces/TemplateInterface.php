<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;

interface TemplateInterface
{
    /**
     * 加载模板
     * @param $file
     * @param bool $force
     * @return string
     */
    public static function loadTemplate(string $file, bool $force = false);
}
