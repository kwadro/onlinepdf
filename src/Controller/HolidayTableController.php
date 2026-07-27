<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HolidayTable;
use App\Entity\HolidayTableRecipe;
use App\Entity\User;
use App\Form\HolidayTableFormType;
use App\Repository\HolidayTableRepository;
use App\Repository\RecipeServiceRepository;
use App\Service\HolidayTableProductCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class HolidayTableController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/{_locale}/holiday-table', name: 'holiday_table', methods: ['GET'])]
    public function index(
        Request $request,
        RecipeServiceRepository $recipeServiceRepository,
        HolidayTableRepository $holidayTableRepository,
    ): Response {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->render('holiday-table/index.html.twig', [
                'form' => null,
            ]);
        }

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login', [
                '_locale' => $request->getLocale(),
            ]);
        }

        $publishedRecipes = $recipeServiceRepository->findPublishedForPicker(
            $site->getId(),
            $localeObject->getId(),
        );
        $recipeChoices = $this->buildRecipeChoices($publishedRecipes);
        $recipeOptions = $this->buildRecipeOptions($publishedRecipes);

        $form = $this->createForm(HolidayTableFormType::class, [
            'holiday_table_id' => '',
            'event_name' => '',
            'event_date' => null,
            'men_count' => 5,
            'women_count' => 5,
            'recipes' => [],
        ], [
            'recipe_choices' => $recipeChoices,
        ]);

        $user = $this->getUser();
        $savedEvents = [];
        $requiresEventSelection = $user instanceof User;

        if ($requiresEventSelection) {
            $savedEvents = $this->serializeSavedEvents(
                $holidayTableRepository->findForUserAccount(
                    (int) $user->getId(),
                    (int) $site->getId(),
                    (int) $localeObject->getId(),
                ),
            );
        }

        return $this->render('holiday-table/index.html.twig', [
            'form' => $form->createView(),
            'recipeOptions' => $recipeOptions,
            'savedEvents' => $savedEvents,
            'requiresEventSelection' => $requiresEventSelection,
            'calculateUrl' => $this->generateUrl('holiday_table_calculate', [
                '_locale' => $request->getLocale(),
            ]),
            'saveUrl' => $this->generateUrl('holiday_table_save', [
                '_locale' => $request->getLocale(),
            ]),
            'loadEventUrl' => $this->generateUrl('holiday_table_load', [
                '_locale' => $request->getLocale(),
                'id' => 0,
            ]),
        ]);
    }

    #[Route('/{_locale}/holiday-table/event/{id}', name: 'holiday_table_load', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function loadEvent(
        int $id,
        Request $request,
        HolidayTableRepository $holidayTableRepository,
    ): JsonResponse {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');
        $user = $this->getUser();

        if (!$site || !$localeObject || !$user instanceof User) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $holidayTable = $holidayTableRepository->findOneForUserAccount(
            $id,
            (int) $user->getId(),
            (int) $site->getId(),
            (int) $localeObject->getId(),
        );

        if ($holidayTable === null) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeHolidayTable($holidayTable));
    }

    #[Route('/{_locale}/holiday-table/calculate', name: 'holiday_table_calculate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function calculate(
        Request $request,
        RecipeServiceRepository $recipeServiceRepository,
        HolidayTableProductCalculator $calculator,
    ): JsonResponse {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->json(['error' => 'Site context is missing'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createFormFromRequest($request, $recipeServiceRepository, $site, $localeObject);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->json(['errors' => $this->collectFormErrors($form)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $form->getData();
        $menCount = (int) ($data['men_count'] ?? 0);
        $womenCount = (int) ($data['women_count'] ?? 0);
        $recipeIds = array_map('intval', $data['recipes'] ?? []);

        $result = $calculator->calculate(
            $recipeIds,
            $menCount,
            $womenCount,
            $site->getId(),
            $localeObject->getId(),
        );

        return $this->json($result);
    }

    #[Route('/{_locale}/holiday-table/save', name: 'holiday_table_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function save(
        Request $request,
        RecipeServiceRepository $recipeServiceRepository,
        EntityManagerInterface $entityManager,
        HolidayTableRepository $holidayTableRepository,
    ): JsonResponse {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->json(['error' => 'Site context is missing'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createFormFromRequest(
            $request,
            $recipeServiceRepository,
            $site,
            $localeObject,
            ['Default', 'save'],
        );
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->json(['errors' => $this->collectFormErrors($form)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $form->getData();
        $menCount = (int) ($data['men_count'] ?? 0);
        $womenCount = (int) ($data['women_count'] ?? 0);
        $recipeIds = array_map('intval', $data['recipes'] ?? []);
        $holidayTableId = (int) ($form->get('holiday_table_id')->getData() ?: 0);

        if ($holidayTableId > 0) {
            $holidayTable = $holidayTableRepository->findOneForUserAccount(
                $holidayTableId,
                (int) $user->getId(),
                (int) $site->getId(),
                (int) $localeObject->getId(),
            );

            if ($holidayTable === null) {
                return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
            }

            $this->updateHolidayTable(
                $entityManager,
                $holidayTable,
                (string) ($data['event_name'] ?? ''),
                $data['event_date'] ?? null,
                $menCount + $womenCount,
                $menCount,
                $womenCount,
                $recipeIds,
            );
        } else {
            $holidayTable = $this->createHolidayTable(
                $entityManager,
                $user,
                $site,
                $localeObject,
                (string) ($data['event_name'] ?? ''),
                $data['event_date'] ?? null,
                $menCount + $womenCount,
                $menCount,
                $womenCount,
                $recipeIds,
            );
        }

        return $this->json([
            'success' => true,
            'id' => $holidayTable->getId(),
            'message' => $this->translator->trans('holiday_table.saved_success'),
            'event' => $this->serializeHolidayTable($holidayTable),
        ]);
    }

    private function createFormFromRequest(
        Request $request,
        RecipeServiceRepository $recipeServiceRepository,
        object $site,
        object $localeObject,
        array $validationGroups = ['Default'],
    ) {
        $recipeChoices = $this->buildRecipeChoices(
            $recipeServiceRepository->findPublishedForPicker($site->getId(), $localeObject->getId()),
        );

        return $this->createForm(HolidayTableFormType::class, null, [
            'recipe_choices' => $recipeChoices,
            'validation_groups' => $validationGroups,
        ]);
    }

    /**
     * @return list<string>
     */
    private function collectFormErrors($form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $message = $error->getMessage();
            $errors[] = str_starts_with($message, 'holiday_table.')
                ? $this->translator->trans($message, [], 'messages')
                : $message;
        }

        return $errors;
    }

    /**
     * @param iterable $recipes
     *
     * @return array<string, int>
     */
    private function buildRecipeChoices(iterable $recipes): array
    {
        $choices = [];
        foreach ($this->buildRecipeOptions($recipes) as $option) {
            $choices[$option['name']] = $option['id'];
        }

        return $choices;
    }

    /**
     * @param iterable $recipes
     *
     * @return list<array{id: int, name: string}>
     */
    private function buildRecipeOptions(iterable $recipes): array
    {
        $options = [];
        foreach ($recipes as $recipe) {
            $translation = $recipe->getRecipetranslations()?->first() ?: null;
            $label = $translation?->getName() ?: ('Recipe #' . $recipe->getId());
            $options[] = [
                'id' => (int) $recipe->getId(),
                'name' => (string) $label,
            ];
        }

        usort($options, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $options;
    }

    /**
     * @param list<HolidayTable> $events
     *
     * @return list<array{id: int, name: string, date: ?string, guests: int, recipes_count: int}>
     */
    private function serializeSavedEvents(array $events): array
    {
        return array_map(fn (HolidayTable $event): array => $this->serializeSavedEventSummary($event), $events);
    }

    /**
     * @return array{id: int, name: string, date: ?string, guests: int, recipes_count: int}
     */
    private function serializeSavedEventSummary(HolidayTable $holidayTable): array
    {
        return [
            'id' => (int) $holidayTable->getId(),
            'name' => (string) ($holidayTable->getEventName() ?? ''),
            'date' => $holidayTable->getEventDate()?->format('Y-m-d'),
            'guests' => (int) ($holidayTable->getGuestCount() ?? 0),
            'recipes_count' => $holidayTable->getHolidaytablerecipes()?->count() ?? 0,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     event_name: string,
     *     event_date: ?string,
     *     men_count: int,
     *     women_count: int,
     *     guest_count: int,
     *     recipes: list<int>
     * }
     */
    private function serializeHolidayTable(HolidayTable $holidayTable): array
    {
        $recipeIds = [];
        $items = $holidayTable->getHolidaytablerecipes()?->toArray() ?? [];
        usort($items, static fn (HolidayTableRecipe $a, HolidayTableRecipe $b): int =>
            ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0));

        foreach ($items as $item) {
            if ($item->getRecipe()?->getId() !== null) {
                $recipeIds[] = (int) $item->getRecipe()->getId();
            }
        }

        return [
            'id' => (int) $holidayTable->getId(),
            'event_name' => (string) ($holidayTable->getEventName() ?? ''),
            'event_date' => $holidayTable->getEventDate()?->format('Y-m-d'),
            'men_count' => (int) ($holidayTable->getMenCount() ?? 0),
            'women_count' => (int) ($holidayTable->getWomenCount() ?? 0),
            'guest_count' => (int) ($holidayTable->getGuestCount() ?? 0),
            'recipes' => $recipeIds,
        ];
    }

    /**
     * @param int[] $recipeIds
     */
    private function createHolidayTable(
        EntityManagerInterface $entityManager,
        User $user,
        object $site,
        object $localeObject,
        string $eventName,
        ?\DateTimeImmutable $eventDate,
        int $guestCount,
        int $menCount,
        int $womenCount,
        array $recipeIds,
    ): HolidayTable {
        $holidayTable = new HolidayTable();
        $holidayTable->setUser($user);
        $holidayTable->setSite($site);
        $holidayTable->setLocale($localeObject);
        $this->applyHolidayTableData($holidayTable, $eventName, $eventDate, $guestCount, $menCount, $womenCount, $recipeIds, $entityManager);

        $entityManager->persist($holidayTable);
        $entityManager->flush();

        return $holidayTable;
    }

    /**
     * @param int[] $recipeIds
     */
    private function updateHolidayTable(
        EntityManagerInterface $entityManager,
        HolidayTable $holidayTable,
        string $eventName,
        ?\DateTimeImmutable $eventDate,
        int $guestCount,
        int $menCount,
        int $womenCount,
        array $recipeIds,
    ): void {
        foreach ($holidayTable->getHolidaytablerecipes()?->toArray() ?? [] as $existingRecipe) {
            $holidayTable->removeHolidayTableRecipe($existingRecipe);
            $entityManager->remove($existingRecipe);
        }

        $this->applyHolidayTableData($holidayTable, $eventName, $eventDate, $guestCount, $menCount, $womenCount, $recipeIds, $entityManager);
        $entityManager->flush();
    }

    /**
     * @param int[] $recipeIds
     */
    private function applyHolidayTableData(
        HolidayTable $holidayTable,
        string $eventName,
        ?\DateTimeImmutable $eventDate,
        int $guestCount,
        int $menCount,
        int $womenCount,
        array $recipeIds,
        EntityManagerInterface $entityManager,
    ): void {
        $holidayTable->setEventName($eventName);
        $holidayTable->setEventDate($eventDate);
        $holidayTable->setGuestCount($guestCount);
        $holidayTable->setMenCount($menCount);
        $holidayTable->setWomenCount($womenCount);

        foreach ($recipeIds as $position => $recipeId) {
            $recipe = $entityManager->getReference(\App\Entity\Recipe::class, $recipeId);
            $holidayTableRecipe = new HolidayTableRecipe();
            $holidayTableRecipe->setRecipe($recipe);
            $holidayTableRecipe->setPosition($position + 1);
            $holidayTable->addHolidayTableRecipe($holidayTableRecipe);
        }
    }
}
