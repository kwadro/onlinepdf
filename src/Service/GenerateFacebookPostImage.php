<?php

namespace App\Service;

use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use SplFileInfo;


class GenerateFacebookPostImage
{
    const IMAGE_WIDTH = 1200;
    const IMAGE_HEIGHT = 630;

    public function __construct(
        private readonly string $generateDirectory,
        private readonly string $uploadRecipeDirectory,
        private readonly FileService $fileService
    ) {
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    public function execute($entity): string
    {
        $image = new Imagick();
        $image->newImage(self::IMAGE_WIDTH, self::IMAGE_HEIGHT, new ImagickPixel('#f7f1ea'));
        $image->setImageFormat('png');
        $background = new ImagickDraw();
        $background->setFillColor(new ImagickPixel('#f7f1ea'));
        $background->rectangle(0, 0, self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        $image->drawImage($background);
        $title = new ImagickDraw();
        $title->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf');
        $title->setFontSize(54);
        $title->setFillColor(new ImagickPixel('#8b1e2d'));

        $image->annotateImage($title, 60, 100, 0, 'Домашні');
        $image->annotateImage($title, 60, 170, 0, 'ВАРЕНИКИ');

        $subtitle = new ImagickDraw();
        $subtitle->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf');
        $subtitle->setFontSize(42);
        $subtitle->setFillColor(new ImagickPixel('#5c4033'));
        $image->annotateImage($subtitle, 60, 230, 0, 'з вишнями');

        $info = new ImagickDraw();
        $info->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf');
        $info->setFontSize(28);
        $info->setFillColor(new ImagickPixel('#4a2d20'));

        $image->annotateImage($info, 60, 330, 0, '• Соковита вишнева начинка');
        $image->annotateImage($info, 60, 380, 0, '• Ніжне домашнє тісто');
        $image->annotateImage($info, 60, 430, 0, '• Смак дитинства');

        $time = new ImagickDraw();
        $time->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf');
        $time->setFontSize(24);
        $time->setFillColor(new ImagickPixel('#8b1e2d'));

        $image->annotateImage($time, 60, 530, 0, 'Підготовка: 35 хв');
        $image->annotateImage($time, 320, 530, 0, 'Варіння: 15 хв');
        $this->fileService->checkDirectory($this->uploadRecipeDirectory);
        $foodImagePath = $this->uploadRecipeDirectory . '/' . $entity->getImage();

        if (file_exists($foodImagePath)) {
            $food = new Imagick($foodImagePath);
            $food->resizeImage(520, 520, Imagick::FILTER_LANCZOS, 1);
            $mask = new Imagick();
            $mask->newImage(520, 520, new ImagickPixel('transparent'));
            $maskDraw = new ImagickDraw();
            $maskDraw->setFillColor(new ImagickPixel('#ffffff'));
            $maskDraw->roundRectangle(0, 0, 520, 520, 40, 40);
            $mask->drawImage($maskDraw);
            $food->setImageMatte(true);
            $food->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0);
            $image->compositeImage($food, Imagick::COMPOSITE_OVER, 640, 55);
        }

        $ornament = new ImagickDraw();
        $ornament->setFillColor(new ImagickPixel('#8b1e2d'));
        $ornament->rectangle(0, 590, 1200, 630);
        $image->drawImage($ornament);

        $footer = new ImagickDraw();
        $footer->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf');
        $footer->setFontSize(20);
        $footer->setFillColor(new ImagickPixel('#ffffff'));

        $image->annotateImage(
            $footer,
            50,
            618,
            0,
            'Подавайте зі сметаною або цукровою пудрою'
        );

        list ($filename , $extension) = $this->fileService->getInfoByPath($foodImagePath);
        $this->fileService->checkDirectory($this->generateDirectory);
        $outputPath = $this->generateDirectory . '/' . $filename . '-facebook-post.' . $extension;
        $image->writeImage($outputPath);
        return $outputPath;
    }
}
