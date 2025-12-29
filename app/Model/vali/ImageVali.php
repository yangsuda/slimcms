<?php

namespace App\Model\vali;

use Respect\Validation\Rules\AbstractRule;

class ImageVali extends AbstractRule
{
    protected $template = '请按照要求规范提交信息';

    // 支持的图片格式
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    public function validate($input): bool
    {
        if (empty($input)) {
            return true;
        }
        $list = explode(',', (string)$input);
        foreach ($list as $v) {
            if (!is_string($v) || strpos($v, '/') !== 0 || !preg_match('/^[a-zA-Z0-9-_,\.\/\?#]+$/', $v)) {
                return false;
            }
            // 验证以图片扩展名结尾
            $extension = strtolower(pathinfo($v, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->allowedExtensions)) {
                return false;
            }
        }
        return true;
    }
}
