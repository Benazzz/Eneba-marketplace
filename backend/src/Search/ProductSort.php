<?php

namespace App\Search;

/**
 * Sort orders for the product list (PLAN.md §4). A backed enum, like a C# enum with string values:
 * ProductSort::from('price_asc') turns the query string value into a case, and an unknown value is a 422.
 */
enum ProductSort: string
{
    /** Best match for the search term, same ranking as the header dropdown. Only used when q is set. */
    case Relevance = 'relevance';
    case Popular = 'popular';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case Discount = 'discount';
    case Newest = 'newest';

    /**
     * The ORDER BY for this sort, over the columns of ProductSearch's FROM clause.
     * Every order ends with name and id, so two products never tie and pages don't overlap or skip items.
     * NULLS LAST puts sold-out products (no "From" price) at the end of price and discount sorts.
     */
    public function orderBy(): string
    {
        $order = match ($this) {
            // :q is bound whenever q is set; ProductSearch only uses Relevance then.
            self::Relevance => 'starts_with(lower(p.name), :q) DESC, word_similarity(:q, lower(p.name)) DESC, COALESCE(sales.sold, 0) DESC',
            self::Popular => 'COALESCE(sales.sold, 0) DESC',
            self::PriceAsc => 'stock.from_price_cents ASC NULLS LAST',
            self::PriceDesc => 'stock.from_price_cents DESC NULLS LAST',
            // Same idea as ProductCard::discountPercent(): how far the "From" price is below MSRP.
            self::Discount => '(p.msrp_cents - stock.from_price_cents)::numeric / NULLIF(p.msrp_cents, 0) DESC NULLS LAST',
            self::Newest => 'p.release_date DESC',
        };

        return $order.', p.name, p.id';
    }
}
