<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/6 20:40
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore\Contract;

interface ApiInterface
{
    public function getUrl():string;
    public function getParams():array;
    public function getMethod():string;
}