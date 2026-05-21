<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;


class GenerateFacebookPostImage
{
    const IMAGE_WIDTH = 1200;
    const IMAGE_HEIGHT = 630;

    public function __construct(
        private readonly string $generateDirectory,
        private readonly string $uploadRecipeDirectory,
        private readonly FileService $fileService,
        private readonly EntityManagerInterface $em

    ) {
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    public function execute($entity): string
    {
        $translation = $entity->getRecipeTranslations()[0];
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
        $titles = explode("\n", $translation->getName());
        $image->annotateImage($title, 60, 100, 0, $titles[0]);
        $startDescription = 220;
        if (isset($titles[1])) {
            $startDescription = 270;
            $image->annotateImage($title, 60, 170, 0, mb_strtoupper($titles[1]));
        }
        if (isset($titles[2])) {
            $startDescription = 330;
            $subtitle = new ImagickDraw();
            $subtitle->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf');
            $subtitle->setFontSize(42);
            $subtitle->setFillColor(new ImagickPixel('#5c4033'));
            $image->annotateImage($subtitle, 60, 230, 0, $titles[2]);
        }
        $info = new ImagickDraw();
        $info->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf');
        $info->setFontSize(28);
        $info->setFillColor(new ImagickPixel('#4a2d20'));
        $descriptions = explode("\n", $translation->getShortDescription());
        $image->annotateImage($info, 60, $startDescription, 0, '• ' . $descriptions[0]);
        if (isset($descriptions[1])) {
            $image->annotateImage($info, 60, $startDescription + 50, 0, '• ' . $descriptions[1]);
        }
        if (isset($descriptions[2])) {
            $image->annotateImage($info, 60, $startDescription + 100, 0, '• ' . $descriptions[2]);
        }

        $time = new ImagickDraw();
        $time->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf');
        $time->setFontSize(24);
        $time->setFillColor(new ImagickPixel('#8b1e2d'));

        $image->annotateImage($time, 60, 530, 0, sprintf('Підготовка : %s хв', $entity->getPrepTimeMin()));
        $image->annotateImage($time, 320, 530, 0, sprintf('Готувати : %s хв', $entity->getCookTimeMin()));

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
            $translation->getNotes()
        );

        list ($filename, $extension) = $this->fileService->getInfoByPath($foodImagePath);
        $this->fileService->checkDirectory($this->generateDirectory);
        $filename = $filename . '-facebook-post.' . $extension;
        $outputPath = $this->generateDirectory . '/' . $filename;
        $image->writeImage($outputPath);
        $translation->setFacebookImage($filename);
        $this->em->persist($translation);
        $this->em->flush();
        return $outputPath;
    }

}
