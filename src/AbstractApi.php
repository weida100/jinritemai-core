<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/6 20:42
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore;

use Weida\JinritemaiCore\Contract\ApiInterface;

abstract class AbstractApi implements ApiInterface
{
    /**
     * @var string api地址
     */
    protected string $_url = '';

    protected string $_method= '';


    /**
     * @return string
     * @author Weida
     */
    public function getUrl(): string
    {
        return $this->_url;
    }

    /**
     * @return array
     * @author Weida
     */
    public function getParams(): array
    {
        $vars = get_object_vars($this);
        unset($vars['_url']);
        unset($vars['_method']);
        $vars = array_filter($vars);
        return $vars;
    }

    public function getMethod(): string
    {
        return $this->_method;
    }



}