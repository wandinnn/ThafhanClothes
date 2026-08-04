<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pencarian produk yang toleran typo dan singkatan pendek
 * (mis. "clana" → Celana, "jkt" → Jaket).
 */
class ProductSearch
{
    public const MIN_SCORE = 38.0;

    public const SUGGESTION_MIN_SCORE = 42.0;

    /**
     * @return Collection<int, Product>
     */
    public static function search(string $query, ?Builder $baseQuery = null, int $limit = 100): Collection
    {
        $normalized = self::normalize($query);

        if ($normalized === '') {
            return collect();
        }

        $products = ($baseQuery ?? Product::query())
            ->with('category')
            ->limit(2000)
            ->get();

        return self::rank($products, $normalized, self::MIN_SCORE)
            ->take($limit)
            ->values();
    }

    /**
     * Saran autocomplete untuk search bar.
     *
     * @return list<array{name: string, slug: string, price: string, image: string, category: string, score: float}>
     */
    public static function suggest(string $query, int $limit = 6): array
    {
        return self::search($query, null, $limit)
            ->filter(fn (Product $product): bool => self::score($product, self::normalize($query)) >= self::SUGGESTION_MIN_SCORE)
            ->take($limit)
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->formatted_price,
                'image' => $product->image_url,
                'category' => $product->category->name ?? '',
                'score' => self::score($product, self::normalize($query)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Product>|iterable<Product>  $products
     * @return Collection<int, Product>
     */
    public static function rank(iterable $products, string $normalizedQuery, float $minScore = self::MIN_SCORE): Collection
    {
        $scored = [];

        foreach ($products as $product) {
            $score = self::score($product, $normalizedQuery);

            if ($score >= $minScore) {
                $scored[] = ['product' => $product, 'score' => $score];
            }
        }

        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return collect($scored)->map(fn (array $row): Product => $row['product']);
    }

    public static function topSuggestionName(string $query, iterable $products): ?string
    {
        $normalized = self::normalize($query);

        if ($normalized === '') {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($products as $product) {
            $score = self::score($product, $normalized);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $product;
            }
        }

        if ($best === null || $bestScore < self::SUGGESTION_MIN_SCORE) {
            return null;
        }

        if (self::normalize($best->name) === $normalized) {
            return null;
        }

        return $best->name;
    }

    public static function score(Product $product, string $normalizedQuery): float
    {
        $name = self::normalize($product->name);
        $description = self::normalize((string) $product->description);
        $category = self::normalize((string) ($product->category->name ?? ''));

        if ($normalizedQuery === '' || $name === '') {
            return 0.0;
        }

        $score = 0.0;

        if ($name === $normalizedQuery || $category === $normalizedQuery) {
            return 120.0;
        }

        if (str_contains($name, $normalizedQuery) || str_contains($category, $normalizedQuery)) {
            $score += 45.0;
        }

        similar_text($normalizedQuery, $name, $namePct);
        similar_text($normalizedQuery, $category, $categoryPct);
        similar_text($normalizedQuery, $description, $descriptionPct);

        $score += ($namePct * 0.55) + ($categoryPct * 0.2) + ($descriptionPct * 0.08);

        $score += self::bestLevenshteinScore($normalizedQuery, $name) * 0.35;
        $score += self::bestLevenshteinScore($normalizedQuery, $category) * 0.15;

        // Singkatan / skeleton konsonan: "jkt" ≈ "jaket"
        $querySkeleton = self::consonants($normalizedQuery);
        $nameSkeleton = self::consonants($name);
        $categorySkeleton = self::consonants($category);

        if ($querySkeleton !== '' && ($querySkeleton === $nameSkeleton || $querySkeleton === $categorySkeleton)) {
            $score += 48.0;
        } elseif ($querySkeleton !== '' && (
            str_contains($nameSkeleton, $querySkeleton)
            || str_contains($categorySkeleton, $querySkeleton)
            || self::isSubsequence($querySkeleton, $nameSkeleton)
            || self::isSubsequence($querySkeleton, $categorySkeleton)
        )) {
            $score += 32.0;
        }

        // Huruf query muncul berurutan di nama (j-k-t di "jaket")
        if (self::isSubsequence($normalizedQuery, $name) || self::isSubsequence($normalizedQuery, $category)) {
            $score += 28.0;
        }

        $bestWordBoost = 0.0;

        foreach (preg_split('/\s+/u', $name) ?: [] as $word) {
            if ($word === '') {
                continue;
            }

            $bestWordBoost = max($bestWordBoost, self::bestLevenshteinScore($normalizedQuery, $word) * 0.12);

            if (self::consonants($normalizedQuery) !== '' && self::consonants($normalizedQuery) === self::consonants($word)) {
                $bestWordBoost = max($bestWordBoost, 22.0);
            }
        }

        $score += $bestWordBoost;

        return round($score, 2);
    }

    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    private static function consonants(string $value): string
    {
        $value = preg_replace('/[^a-z0-9]+/u', '', mb_strtolower($value)) ?? '';

        return preg_replace('/[aeiou]+/u', '', $value) ?? '';
    }

    private static function bestLevenshteinScore(string $query, string $target): float
    {
        if ($target === '') {
            return 0.0;
        }

        $candidates = [$target, ...preg_split('/\s+/u', $target) ?: []];
        $best = 0.0;

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            // levenshtein() hanya andal untuk string pendek ASCII-ish
            if (strlen($query) > 255 || strlen($candidate) > 255) {
                continue;
            }

            $distance = levenshtein($query, $candidate);
            $maxLen = max(strlen($query), strlen($candidate), 1);
            $best = max($best, max(0, 1 - ($distance / $maxLen)) * 100);
        }

        return $best;
    }

    private static function isSubsequence(string $needle, string $haystack): bool
    {
        if ($needle === '' || $haystack === '') {
            return false;
        }

        $needleLength = mb_strlen($needle);
        $haystackLength = mb_strlen($haystack);
        $needleIndex = 0;

        for ($i = 0; $i < $haystackLength && $needleIndex < $needleLength; $i++) {
            if (mb_substr($haystack, $i, 1) === mb_substr($needle, $needleIndex, 1)) {
                $needleIndex++;
            }
        }

        return $needleIndex === $needleLength;
    }
}
