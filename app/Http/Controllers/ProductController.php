<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    /**
     * Walks attributes and deeply nested codes to a single string.
     * 
     * For example:
     * resolveName("cat_1_1", [
     *      [
     *          "code" => "cat_1_1",
     *          "name" => "Electric bikes"
     *      ],
     *      [
     *          "code" => "cat_1",
     *          "name" => "Bikes",
     *      ]
     * ])
     * returns "Bikes > Electric bikes"
     */
    protected function resolveName(string $key, Arrayable $attributes): string
    {
        $sections = substr_count($key, '_');
        $name = $attributes[$key];

        if ($sections < 2) {
            return $name;
        }

        return $this->resolveName(Str::beforeLast($key, '_'), $attributes) . ' > ' . $name;
    }

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'numeric|min:1',
            'page_size' => 'numeric|min:1'
        ]);

        $page = $request->get('page', 1);
        $page_size = $request->get('page_size', 10);

        [
            'products' => $products,
            'attributes' => $attributes
        ] = Http::pool(
            fn($pool) => [
                $pool->as('products')->get('https://draft.grebban.com/backend/products.json'),
                $pool->as('attributes')->get('https://draft.grebban.com/backend/attribute_meta.json'),
            ]
        );

        if (!$products->ok() || !$attributes->ok()) {
            return response([
                'status' => 'error',
                'message' => 'external server returned error'
            ], 503);
        }

        $products = $products->json();
        $attributes = $attributes->json();

        $total = count($products);
        $products = collect($products)
            ->skip($page_size * ($page - 1))
            ->take($page_size);

        $attributes = collect($attributes)->mapWithKeys(function ($attribute) {
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
            $product['attributes'] = collect($product['attributes'])
                ->flatMap(
                    function ($attribute, $index) use ($attributes) {
                        $values = Arr::wrap(explode(',', $attribute));

                        $newAttributes = [];

                        foreach ($values as $value) {
                            $name = $this->resolveName($value, $attributes[$index]['items']);

                            $newAttributes[] = [
                                'name' => $attributes[$index]['name'],
                                'value' => $name
                            ];
                        }

                        return $newAttributes;
                    }
                );

            $products[$index] = $product;
        }

        return [
            'products' => $products->values(),
            'page' => $page,
            'totalPages' => ceil($total / $page_size),
        ];
    }
}
