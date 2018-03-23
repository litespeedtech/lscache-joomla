<?php

/* 
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentK2 extends LSCacheComponentBase{

    public function onPurgeContent($context, $row){
        if($context == "com_k2.item"){
            return 'com_k2,com_k2:' . $row->id;
        }
        else{
            return 'com_k2';
        }
   }
}