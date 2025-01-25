<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function resolveName($key, $attributes)
    {
        $subItems = substr_count($key, '_');
        $name = $attributes[$key];
        if ($subItems < 2) {
            return $name;
        }

        return $this->resolveName(str($key)->beforeLast('_')->toString(), $attributes) . ' > ' . $name;
    }

    public function index(Request $request)
    {
        Http::fake([
            'https://draft.grebban.com/backend/products.json' => Http::response(
                '[{"id": 6267654,"name": "Auto Omega","attributes": {"cat": "cat_2","color": "black,white"}},{"id": 8094994,"name": "Bike Alef","attributes": {"color": "black,white","cat": "cat_1"}},{"id": 2846132,"name": "Bike Bet","attributes": {"color": "blue","cat": "cat_1_1,cat_1_2"}},{"id": 2169396,"name": "Auto Alpha","attributes": {"color": "green,blue","cat": "cat_2_2"}},{"id": 2749899,"name": "Auto Delta","attributes": {"color": "red","cat": "cat_2_2"}},{"id": 3311138,"name": "Auto Gamma","attributes": {"cat": "cat_2_3,cat_2_2","color": "black,white"}},{"id": 4364807,"name": "Bike Gimel","attributes": {"color": "red,white,green","cat": "cat_1_1,cat_1_2"}},{"id": 5385176,"name": "Bike Dalet","attributes": {"color": "black","cat": "cat_1_2"}},{"id": 12345,"name": "Random product","attributes": {}}]',
                200,
                ['Content-Type' => 'application/json']
            ),
            'https://draft.grebban.com/backend/attribute_meta.json' => Http::response(
                '[{"name": "Color","code": "color","values": [{"name": "Brown","code": "brown"},{"name": "Black","code": "black"},{"name": "White","code": "white"},{"name": "Blue","code": "blue"},{"name": "Green","code": "green"},{"name": "Red","code": "red"}]},{"name": "Category","code": "cat","values": [{"name": "Electric bikes","code": "cat_1_1"},{"name": "Bikes","code": "cat_1"},{"name": "BMX","code": "cat_1_2"},{"name": "Cars","code": "cat_2"},{"name": "Electric cars","code": "cat_2_1"},{"name": "Hybrids","code": "cat_2_2"},{"name": "Sportscars","code": "cat_2_3"}]}]',
                200,
                ['Content-Type' => 'application/json', 'X-Foo' => 'Bar']
            ),
        ]);

        $page = $request->get('page', 1);
        $page_size = $request->get('page_size', 10);

        ['products' => $products, 'attributes' => $attributes] = Http::pool(fn($pool) => [
            $pool->as('products')->get('https://draft.grebban.com/backend/products.json'),
            $pool->as('attributes')->get('https://draft.grebban.com/backend/attribute_meta.json'),
        ]);

        $products = $products->json();
        $attributes = $attributes->json();

        $total = count($products);
        $products = collect($products)->skip($page_size * ($page - 1))->take($page_size);

        $attributes = collect($attributes)->mapWithKeys(function ($attribute) {
            usort($attribute['values'], function ($a, $b) {
                return $a['code'] <=> $b['code'];
            });

            $values = collect($attribute['values'])->mapWithKeys(function ($attribute) {
                return [$attribute['code'] => $attribute['name']];
            });

            return [
                $attribute['code'] => [
                    'name' => $attribute['name'],
                    'items' => $values,
                ]
            ];
        });

        foreach ($products as $index => $product) {
            $pAttributes = $product['attributes'];

            $newAttributes = [];

            foreach ($pAttributes as $pIndex => $attribute) {
                $nested = Arr::wrap(explode(',', $attribute));

                foreach ($nested as $nv) {
                    $name = $this->resolveName($nv, $attributes[$pIndex]['items']);
                    $newAttributes[] = [
                        'name' => $attributes[$pIndex]['name'],
                        'value' => $name
                    ];
                }
                if (strpos($attribute, ',') === false) {
                }
            }

            $product['attributes'] = $newAttributes;

            $products[$index] = $product;
        }

        return [
            'products' => $products->values(),
            'page' => $page,
            'totalPages' => ceil($total / $page_size),
        ];
    }
}
