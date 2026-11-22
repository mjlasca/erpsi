<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use App\Modules\Sales\Actions\GetSalesForDatesAction;

#[Name('get-sales-for-date')]
#[Title('Get sales for a specific date')]
#[Description('Fetches sales data for a specified date.')]
#[IsIdempotent]
#[IsReadOnly]
class SalesTool extends Tool
{
    public function __construct(
        protected GetSalesForDatesAction $getSalesForDatesAction
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'startDate' => [
                'required',
                'date_format:Y-m-d H:i:s',
            ],
            'endDate' => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:startDate',
            ],
        ]);

        $result = $this->getSalesForDatesAction->execute(
            $validated['startDate'],
            $validated['endDate']
        );

        return Response::structured($result->toArray());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'startDate' => $schema->string()
                ->description(
                    'Start date format YYYY-MM-DD HH:MM:SS. Example: 2026-11-21 00:00:00'
                )
                ->required(),
            'endDate' => $schema->string()
                ->description(
                    'EndDate format YYYY-MM-DD HH:MM:SS. Example: 2026-11-21 23:59:59'
                )
                ->required(),
        ];
    }
}
