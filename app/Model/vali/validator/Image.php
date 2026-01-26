<?php

namespace App\Model\vali\validator;

use Respect\Validation\Rules\AbstractRule;

class Image extends AbstractRule
{
    protected $template = '请按照要求规范提交信息';

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
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                return false;
            }
        }
        return true;
    }
}
