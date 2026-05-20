<?php

namespace App\Service;

use SplFileInfo;

class FileService
{
     public function getInfoByPath($filePath){
         $info = new SplFileInfo($filePath);
         $filename = pathinfo($filePath, PATHINFO_FILENAME);
         $extension = $info->getExtension();
         return [$filename,$extension];
     }
     public function checkDirectory($dirPath): void
     {
         if (!is_dir($dirPath)) {
             mkdir($dirPath, 0777, true);
         }
     }
}
