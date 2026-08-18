<?php
declare(strict_types=1);

namespace app\Service\common;

use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Interfaces\UploadInterface;

class FileService extends ServiceAbstract
{
    /**
     * 删除图集中某张图
     * @param int $fid
     * @param int $id
     * @param string $field
     * @param string $pic
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function imgsDel(int $fid, int $id, string $field, string $pic): OutputInterface
    {
        if (empty($fid) || empty($id) || empty($field) || empty($pic)) {
            return $this->output->withCode(21002);
        }
        $table = $this->r(FormsRepository::class)->withWhere(['id' => $fid])->fetch('table')?->table;
        if (empty($table)) {
            return $this->output->withCode(21001);
        }
        $data = $this->r($this->getRepositoryClassName($table))->withWhere(['id'=>$id])->fetch($field)?->$field;
        if (empty($data)) {
            return $this->output->withCode(21001);
        }
        $pic = str_replace(trim($this->config['basehost'], '/'), '', $pic);
        preg_match('/(.*)_([\d]+)x([\d]+).(.*)/i', $pic, $match);
        if (!empty($match)) {
            $pic = $match[1] . '.' . $match[4];
        }

        $pics = unserialize($data);
        $key = md5($pic);
        if (empty($pics[$key])) {
            return $this->output->withCode(21001);
        }
        unset($pics[$key]);
        $this->container->get(UploadInterface::class)->uploadDel($pic);
        $data = $pics ? serialize($pics) : '';
        $this->r($this->getRepositoryClassName($table))->update($id, [$field => $data]);
        return $this->output->withCode(200);
    }

    /**
     * 删除某个附件
     * @param int $fid
     * @param int $id
     * @param string $identifier
     * @return OutputInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function delImg(int $fid, int $id, string $identifier): OutputInterface
    {
        if (empty($fid) || empty($id) || empty($identifier)) {
            return $this->output->withCode(21002);
        }
        $tableName = $this->r(FormsRepository::class)->getTable($fid);
        if (empty($tableName)) {
            return $this->output->withCode(21001);
        }
        $row = $this->r($this->getRepositoryClassName($tableName))->withWhere(['id' => $id])->fetch($identifier);
        if (empty($row)) {
            return $this->output->withCode(21001);
        }
        $upload = $this->container->get(UploadInterface::class);
        $upload->uploadDel($row->$identifier);
        $this->r($this->getRepositoryClassName($tableName))->update($id, [$identifier => '']);
        return $this->output->withCode(200);
    }

    /**
     * 设置封面
     * @param array $param
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function webuploadCover(int $fid, int $id, string $pic): OutputInterface
    {
        if (empty($id) || empty($fid) || empty($pic)) {
            return $this->output->withCode(21002);
        }
        $tableName = $this->r(FormsRepository::class)->getTable($fid);
        if (empty($tableName)) {
            return $this->output->withCode(21001);
        }
        $fieldname = $this->r(Forms_fieldsRepository::class)->withWhere(['formid' => $fid, 'datatype' => 'imgs'])
            ->fetch('identifier')?->identifier;
        if (empty($fieldname)) {
            return $this->output->withCode(21001);
        }
        $key = md5($pic);
        $pics = $this->r($this->getRepositoryClassName($tableName))->withWhere(['id' => $id])->fetch($fieldname)?->$fieldname;
        if (empty($pics)) {
            return $this->output->withCode(21001);
        }
        $pics = unserialize($pics);
        if (empty($pics[$key])) {
            return $this->output->withCode(21001);
        }
        foreach ($pics as $k => $v) {
            if (isset($v['iscover'])) {
                unset($v['iscover']);
            }
            $pics[$k] = $v;
        }
        $pics[$key]['iscover'] = 1;
        $data = [
            $fieldname => serialize($pics),
        ];
        $this->r($this->getRepositoryClassName($tableName))->withWhere(['id' => $id])->batchUpdate($data);
        return $this->output->withCode(200);
    }

    /**
     * 多附件删除
     * @param array $param
     * @return OutputInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SlimCMS\Error\TextException
     */
    public function delFromAddons(int $fid, int $id, string $identifier, string $url): OutputInterface
    {
        if (empty($fid) || empty($id) || empty($identifier) || empty($url)) {
            return $this->output->withCode(21002);
        }
        $tableName = $this->r(FormsRepository::class)->getTable($fid);
        if (empty($tableName)) {
            return $this->output->withCode(21001);
        }
        $row = $this->r($this->getRepositoryClassName($tableName))->withWhere(['id' => $id])->fetch($identifier);
        if (empty($row)) {
            return $this->output->withCode(21001);
        }
        $upload = $this->container->get(UploadInterface::class);
        $upload->uploadDel($url);
        $arr = unserialize($row->$identifier);
        foreach ($arr as $k => $v) {
            if ($v['url'] == $url) {
                unset($arr[$k]);
            }
        }
        $addons = $arr ? serialize($arr) : '';
        $this->r($this->getRepositoryClassName($tableName))->update($id, [$identifier => $addons]);
        return $this->output->withCode(200);
    }
}
