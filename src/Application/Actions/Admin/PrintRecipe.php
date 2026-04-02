<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Recipe;
use Domain\Repositories\PrintersRepository;
use Domain\Repositories\RecipesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PrintRecipe
{
    public function __construct(
        private RecipesRepository $recipesRepository,
        private PrintersRepository $printersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $recipe = $this->recipesRepository->find($id);

        if ($recipe === null) {
            return $response->withStatus(404);
        }

        $printers = $this->printersRepository->findBy(['isActive' => true], ['name' => 'asc']);

        $printersData = array_values(array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'printerAddress' => $p->getPrinterAddress(),
            'isReceiptPrinter' => $p->getIsReceiptPrinter(),
        ], $printers));

        return $this->twig->render($response, 'admin/print_recipe.twig', [
            'recipeJson' => json_encode($this->serializeRecipe($recipe)),
            'printersJson' => json_encode($printersData),
        ]);
    }

    private function serializeRecipe(Recipe $recipe): array
    {
        $supplies = array_values(array_map(fn($i) => [
            'name' => $i->getSupply()->getName(),
            'quantity' => $i->getQuantity(),
            'unit' => $i->getUnit(),
        ], $recipe->getSupplies()->toArray()));

        $preparations = array_values(array_map(fn($i) => [
            'quantity' => $i->getQuantity(),
            'unit' => $i->getUnit(),
            'recipe' => $this->serializeRecipe($i->getPreparation()),
        ], $recipe->getPreparations()->toArray()));

        return [
            'id' => $recipe->getId(),
            'name' => $recipe->getName(),
            'yield' => $recipe->getYield(),
            'yieldUnit' => $recipe->getYieldUnit(),
            'duration' => $recipe->getDuration(),
            'comments' => $recipe->getComments(),
            'supplies' => $supplies,
            'preparations' => $preparations,
        ];
    }
}
