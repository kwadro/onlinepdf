<?php

namespace App\Service;

use App\Entity\FacebookSetting;
use App\Repository\FacebookSettingServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Exception;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use Symfony\Contracts\Translation\TranslatorInterface;


class GenerateFacebookPostImage
{
    const IMAGE_WIDTH = 1200;
    const IMAGE_HEIGHT = 630;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $generateDirectory,
        private readonly string $uploadRecipeDirectory,
        private readonly FileService $fileService,
        private readonly EntityManagerInterface $em,
        private readonly FacebookSettingServiceRepository $facebookSettingServiceRepository,
        private TranslatorInterface $translator
    ) {
    }

    public function update($entity, $locale, $site): array
    {
        $facebookSetting = $this->facebookSettingServiceRepository
            ->findOneBy([
                'recipe_id' => $entity->getId(),
                'site' => $site->getId(),
                'locale' => $locale->getId()
            ]);
        $templateData = [];
        $templateData['title1'] = $facebookSetting->getTitle1();
        $templateData['title2'] = $facebookSetting->getTitle2();
        $templateData['title3'] = $facebookSetting->getTitle3();
        $templateData['content1'] = $facebookSetting->getContent1();
        $templateData['content2'] = $facebookSetting->getContent2();
        $templateData['content3'] = $facebookSetting->getContent3();
        $templateData['notes'] = $facebookSetting->getNotes();
        $templateData['prep_time'] = $entity->getPrepTimeMin();
        $templateData['cook_time'] = $entity->getCookTimeMin();
        $templateData['image'] = $entity->getImage();
        $templateData['locale'] = $locale;
        $templateData['site'] = $site;
        return $this->updateTemplate($templateData);
    }

    private function formatText($text, $options): ?string
    {
        $lengthText = mb_strlen($text);
        $width = 60;
        $result = '';
        if (!empty($options['align'])) {
            $align = $options['align'];
            if ($align == 'right') {
                if ($lengthText < $width) {
                    $space = str_repeat('|', ($width - $lengthText));
                    $result = $space . $text;
                }
            }
            if ($align == 'left') {
                $result = $text;
            }
            if ($align == 'center') {
                if ($lengthText < $width) {
                    $space = str_repeat(' ', round(($width - $lengthText) / 2));
                    $result = $space . $text;
                }
            }
        }
        return $result;
    }

    private function loadPostTextByRecipe($entity, $locale): string
    {
        $width = 60;
        $translation = $this->getTranslationByEntityAndLocale($entity, $locale);
        $title = $this->translator->trans('Ingredients');
        $text = $this->formatText($title, ['align' => 'center']) . PHP_EOL;

        $groups = $translation->getGroupComponents();
        $textArray = [];
        $maxIngredientLength = 0;
        $maxPropertyLength = 0;
        foreach ($groups as $group) {
            if (!isset($textArray[$group->getName()])) {
                $textArray[$group->getName()] = [];
            }
            $components = $group->getComponents();
            foreach ($components as $component) {
                $ingredient = $component->getIngredient();
                $ingredientName = $ingredient->getName();
                $qty = $component->getQuantity(). ' ';
                $unit = $component->getUnit()->getShortName();
                if($unit === 'за смаком'){
                    $qty = '';
                }
                $ingredientProperty = $qty . $unit;
                $ingredientPropertyLength = mb_strwidth($ingredientProperty);
                $ingredientNameLength = mb_strwidth($ingredientName);
                $textArray[$group->getName()][] = [
                    'name' => $ingredientName,
                    'property' => $ingredientProperty,
                ];
                if ($ingredientNameLength > $maxIngredientLength) {
                    $maxIngredientLength = $ingredientNameLength;
                }
                if ($ingredientPropertyLength > $maxPropertyLength) {
                    $maxPropertyLength = $ingredientNameLength;
                }
            }
        }
        $steps = $translation->getRecipeSteps();
        $title = $this->translator->trans('Steps');
        $stepText = $this->formatText($title, ['align' => 'left']) . PHP_EOL;
        foreach ($steps as $step) {
            $stepText .= sprintf("• %s", $step->getName()).PHP_EOL;;
            $stepText .= sprintf("%s", $step->getAnswer()).PHP_EOL;;
        }


        foreach ($textArray as $groupName=>$groupArray) {
            $text .= $groupName . ':' . PHP_EOL;
            foreach ($groupArray as $itemArray) {
                $name = $itemArray['name'];
                $text .= sprintf("• %s — %s", $name, $itemArray['property']).PHP_EOL;
            }
        }
        return $stepText.$text;
    }

    private function getTranslationByEntityAndLocale($entity, $locale)
    {
        $translation = null;
        foreach ($entity->getRecipeTranslations() as $recipeTranslation) {
            if ($recipeTranslation->getLocale()->getCode() === $locale->getCode()) {
                $translation = $recipeTranslation;
                break;
            }
        }
        return $translation;
    }


    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    public function execute($entity, $site, $locale): string
    {
        $translation = $this->getTranslationByEntityAndLocale($entity, $locale);
        $facebookSetting = $this->facebookSettingServiceRepository
            ->findOneBy([
                    'recipe_id' => $entity->getId(),
                    'site' => $site->getId(),
                    'locale' => $locale->getId()
                ]
            );

        // set data only one time
        if ($facebookSetting) {
            $textPost = $this->loadPostTextByRecipe($entity, $locale);
            $facebookSetting->setTextPost($textPost);
            $this->em->persist($facebookSetting);
            $this->em->flush();
            return $translation->getFacebookImage();
        }
        $facebookSetting = new FacebookSetting();

        $textPost = $this->loadPostTextByRecipe($entity, $locale);
        $facebookSetting->setTextPost($textPost);

        $facebookSetting->setRecipeId($entity->getId());
        $templateData = $this->loadTemplateByEntity($entity, $locale, $site);

        $updateResult = $this->updateTemplate($templateData);
        if ($updateResult['status'] === 'success') {
            $this->saveTemplate($facebookSetting, $templateData);
            $imageOutput = $updateResult['message'];
            $translation->setFacebookImage($imageOutput);
            $this->em->persist($translation);
            $this->saveTemplate($facebookSetting, $templateData);
            $this->em->persist($facebookSetting);
            $this->em->flush();
            return $imageOutput;
        } else {
            return $updateResult['message'];
        }
    }

    public function loadTemplateByEntity($entity, $locale, $site): ?array
    {
        $translation = null;
        foreach ($entity->getRecipeTranslations() as $recipeTranslation) {
            if ($recipeTranslation->getLocale()->getCode() === $locale->getCode()) {
                $translation = $recipeTranslation;
                break;
            }
        }
        if (!$translation) {
            return null;
        }
        $templateData = [];
        $titles = explode("\n", $translation->getName());
        $templateData['title1'] = $titles[0];
        $templateData['title2'] = $titles[1] ?? '';
        $templateData['title3'] = $titles[2] ?? '';
        $descriptions = explode("\n", $translation->getShortDescription());
        $templateData['content1'] = $descriptions[0];
        $templateData['content2'] = $descriptions[1] ?? '';
        $templateData['content3'] = $descriptions[2] ?? '';

        $templateData['notes'] = $translation->getNotes();
        $templateData['prep_time'] = $entity->getPrepTimeMin();
        $templateData['cook_time'] = $entity->getCookTimeMin();
        $templateData['image'] = $entity->getImage();
        $templateData['locale'] = $locale;
        $templateData['site'] = $site;
        return $templateData;
    }

    public function saveTemplate($facebookSetting, $templateData): void
    {
        foreach ($templateData as $key => $value) {
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($facebookSetting, $setter)) {
                $facebookSetting->$setter($value);
            }
        }
    }

    public function updateTemplate($templateData): array
    {
        try {
            $fontPath = $this->projectDir . '/fonts/DejaVuSans.ttf';
            $fontBoldPath = $this->projectDir . '/fonts/DejaVuSans-Bold.ttf';
            $image = new Imagick();
            $image->newImage(self::IMAGE_WIDTH, self::IMAGE_HEIGHT, new ImagickPixel('#f7f1ea'));
            $image->setImageFormat('png');
            $background = new ImagickDraw();
            $background->setFillColor(new ImagickPixel('#f7f1ea'));
            $background->rectangle(0, 0, self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
            $image->drawImage($background);
            $title = new ImagickDraw();
            $title->setFont($fontBoldPath);
            $title->setFontSize(54);
            $title->setFillColor(new ImagickPixel('#8b1e2d'));
            $currentY = 0;
            if (!empty($templateData['title1'])) {
                $currentY += 100;
                $image->annotateImage($title, 60, $currentY, 0, $templateData['title1']);
            }
            if (!empty($templateData['title2'])) {
                $currentY += 70;
                $image->annotateImage($title, 60, $currentY, 0, mb_strtoupper($templateData['title2']));
            }
            if (!empty($templateData['title3'])) {
                $currentY += 60;
                $subtitle = new ImagickDraw();
                $subtitle->setFont($fontPath);
                $subtitle->setFontSize(42);
                $subtitle->setFillColor(new ImagickPixel('#5c4033'));
                $image->annotateImage($subtitle, 60, $currentY, 0, $templateData['title3']);
            }
            $info = new ImagickDraw();
            $info->setFont($fontPath);
            $info->setFontSize(28);
            $info->setFillColor(new ImagickPixel('#4a2d20'));

            if (!empty($templateData['content1'])) {
                $currentY += 100;
                $image->annotateImage($info, 60, $currentY, 0, '• ' . $templateData['content1']);
            }

            if (!empty($templateData['content2'])) {
                $currentY += 50;
                $image->annotateImage($info, 60, $currentY, 0, '• ' . $templateData['content2']);
            }
            if (!empty($templateData['content3'])) {
                $currentY += 50;
                $image->annotateImage($info, 60, $currentY, 0, '• ' . $templateData['content3']);
            }
            $time = new ImagickDraw();
            $time->setFont($fontBoldPath);
            $time->setFontSize(24);
            $time->setFillColor(new ImagickPixel('#8b1e2d'));

            $image->annotateImage($time, 60, 530, 0, sprintf('Підготовка : %s хв', $templateData['prep_time']));
            $image->annotateImage($time, 320, 530, 0, sprintf('Готувати : %s хв', $templateData['cook_time']));

            $this->fileService->checkDirectory($this->uploadRecipeDirectory);
            $foodImagePath = $this->uploadRecipeDirectory . '/' . $templateData['image'];

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
            $footer->setFont($fontPath);
            $footer->setFontSize(20);
            $footer->setFillColor(new ImagickPixel('#ffffff'));

            $image->annotateImage(
                $footer,
                50,
                618,
                0,
                $templateData['notes']
            );
            list ($filename, $extension) = $this->fileService->getInfoByPath($foodImagePath);
            $this->fileService->checkDirectory($this->generateDirectory);
            $filename = $filename . '-facebook-post.' . $extension;
            $outputPath = $this->generateDirectory . '/' . $filename;
            $image->writeImage($outputPath);
            return ['status' => 'success', 'message' => $filename];
        } catch (Exception|ImagickDrawException|ImagickException  $exception) {
            return ['status' => 'error', 'message' => $exception->getMessage()];
        }
    }
}
