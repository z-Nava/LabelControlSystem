<?php

namespace App\ViewModels\Labels;

use App\Models\LabelRequest;
use Illuminate\Support\Collection;

final readonly class LabelRequestIndexRow
{
    /**
     * @param  array<int, string>  $serialPartNumbers
     * @param  array<int, string>  $ratingPartNumbers
     * @param  array<int, string>  $shippingItems
     * @param  Collection<int, string>  $lpkProductionJobs
     */
    private function __construct(
        public LabelRequest $labelRequest,
        public array $serialPartNumbers,
        public array $ratingPartNumbers,
        public ?string $innerItem,
        public array $shippingItems,
        public string $shippingItemSummary,
        public bool $hasGroupedLpkDetails,
        public Collection $lpkProductionJobs,
    ) {}

    public static function from(LabelRequest $labelRequest): self
    {
        $serialPartNumbers = self::formatItems($labelRequest->requestedSerialItems());
        $ratingPartNumbers = self::formatItems($labelRequest->requestedRatingItems());
        $shippingItems = self::formatItems($labelRequest->requestedShippingItems());
        $hasGroupedLpkDetails = $labelRequest->hasGroupedLpkDetails();

        return new self(
            labelRequest: $labelRequest,
            serialPartNumbers: $serialPartNumbers,
            ratingPartNumbers: $ratingPartNumbers,
            innerItem: $labelRequest->include_inner
                ? collect([$labelRequest->inner_part_number, $labelRequest->inner_model])->filter()->implode(' · ')
                : null,
            shippingItems: $shippingItems,
            shippingItemSummary: $shippingItems !== []
                ? implode(', ', array_slice($shippingItems, 0, 2))
                : 'Sin NP capturado',
            hasGroupedLpkDetails: $hasGroupedLpkDetails,
            lpkProductionJobs: $hasGroupedLpkDetails
                ? $labelRequest->lpkLabelGroups->flatMap->items->pluck('job_number')->unique()->values()
                : collect(),
        );
    }

    /**
     * @param  array<int, array{part_number: string, model: ?string}>  $items
     * @return array<int, string>
     */
    private static function formatItems(array $items): array
    {
        return collect($items)
            ->map(fn (array $item): string => $item['part_number'].($item['model'] ? ' · '.$item['model'] : ''))
            ->all();
    }
}
