<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceIntelligenceController extends Controller
{
    /**
     * Procurement Price Intelligence & Cross-Merchant Price Comparison Engine.
     */
    public function priceIntelligenceIndex(Request $request)
    {
        $search = $request->input('search');

        $itemsQuery = DB::table('claim_items')
            ->join('claims', 'claim_items.claim_id', '=', 'claims.claim_id')
            ->select(
                'claim_items.item_name',
                'claims.merchant_name',
                DB::raw('AVG(claim_items.unit_price) as avg_price'),
                DB::raw('MIN(claim_items.unit_price) as min_price'),
                DB::raw('MAX(claim_items.unit_price) as max_price'),
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('MAX(claims.transaction_date) as last_purchased_date')
            )
            ->whereNotNull('claims.merchant_name')
            ->when($search, fn($q) => $q->where('claim_items.item_name', 'like', "%{$search}%"))
            ->groupBy('claim_items.item_name', 'claims.merchant_name')
            ->orderBy('claim_items.item_name')
            ->get();

        $comparisonData = [];
        $grouped = $itemsQuery->groupBy('item_name');

        foreach ($grouped as $itemName => $merchants) {
            $sortedByPrice = $merchants->sortBy('avg_price');
            $cheapest = $sortedByPrice->first();
            $mostExpensive = $sortedByPrice->last();
            $priceDiff = $mostExpensive->avg_price - $cheapest->avg_price;
            $savingsPercent = $mostExpensive->avg_price > 0
                ? round(($priceDiff / $mostExpensive->avg_price) * 100)
                : 0;

            $comparisonData[] = (object) [
                'item_name' => $itemName,
                'total_purchases' => $merchants->sum('purchase_count'),
                'cheapest_merchant' => $cheapest->merchant_name,
                'cheapest_price' => $cheapest->avg_price,
                'expensive_merchant' => $mostExpensive->merchant_name,
                'expensive_price' => $mostExpensive->avg_price,
                'potential_savings_pct' => $savingsPercent,
                'merchant_breakdown' => $merchants
            ];
        }

        $topFrequentItems = DB::table('claim_items')
            ->select('item_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_spend'))
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        return view('manager.price_intelligence', compact('comparisonData', 'topFrequentItems', 'search'));
    }
}
