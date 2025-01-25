<?php

use Illuminate\Support\Facades\Http;

function fake_correct_data()
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
}

function fake_500_error()
{
    Http::fake([
        'https://draft.grebban.com/backend/products.json' => Http::response(
            '',
            500,
            ['Content-Type' => 'application/json']
        ),
        'https://draft.grebban.com/backend/attribute_meta.json' => Http::response(
            '',
            500,
            ['Content-Type' => 'application/json', 'X-Foo' => 'Bar']
        ),
    ]);
}

it('returns correctly formated data', function () {
    fake_correct_data();

    $response = $this->getJson('/product?page=1&page_size=2');
    $response->assertOk();

    $response->assertJson([
        'page' => 1,
        'totalPages' => 5,
        'products' => [
            [
                'id' => 6267654,
                'name' => 'Auto Omega',
                'attributes' => [
                    [
                        'name' => 'Category',
                        'value' => 'Cars',
                    ],
                    [
                        'name' => 'Color',
                        'value' => 'Black',
                    ],
                    [
                        'name' => 'Color',
                        'value' => 'White'
                    ],
                ]
            ]
        ]
    ]);
});

it('defaults to sane values if page size is empty', function () {
    fake_correct_data();

    $response = $this->getJson('/product?page=1');
    $response->assertOk();

    $response->assertJson([
        'page' => 1,
        'totalPages' => 1,
    ]);
});

it('returns empty products if page outside range', function () {
    fake_correct_data();

    $response = $this->getJson('/product?page=100&page_size=10');
    $response->assertOk();

    $response->assertJson([
        'page' => 100,
        'totalPages' => 1,
        'products' => []
    ]);
});

it('disallows non numeric values for input parameters', function () {
    $response = $this->getJson('/product?page=not-a-number&page_size=not-a-number-aswell');

    $response->assertUnprocessable();
});

it('handles if server is missing', function () {
    fake_500_error();

    $response = $this->getJson('/product?page=100&page_size=10');
    $response->assertServiceUnavailable();
});

it('handles missing categories', function () {
    Http::fake([
        'https://draft.grebban.com/backend/products.json' => Http::response(
            '[{"id": 6267654,"name": "Auto Omega","attributes": {"cat": "cat_2","color": "black,white"}},{"id": 8094994,"name": "Bike Alef","attributes": {"color": "black,white","cat": "cat_1"}},{"id": 2846132,"name": "Bike Bet","attributes": {"color": "blue","cat": "cat_1_1,cat_1_2"}},{"id": 2169396,"name": "Auto Alpha","attributes": {"color": "green,blue","cat": "cat_2_2"}},{"id": 2749899,"name": "Auto Delta","attributes": {"color": "red","cat": "cat_2_2"}},{"id": 3311138,"name": "Auto Gamma","attributes": {"cat": "cat_2_3,cat_2_2","color": "black,white"}},{"id": 4364807,"name": "Bike Gimel","attributes": {"color": "red,white,green","cat": "cat_1_1,cat_1_2"}},{"id": 5385176,"name": "Bike Dalet","attributes": {"color": "black","cat": "cat_1_2"}},{"id": 12345,"name": "Random product","attributes": {}}]',
            200,
            ['Content-Type' => 'application/json']
        ),
        'https://draft.grebban.com/backend/attribute_meta.json' => Http::response(
            '[{"name": "Category","code": "cat","values": [{"name": "Electric bikes","code": "cat_1_1"},{"name": "BMX","code": "cat_1_2"},{"name": "Cars","code": "cat_2"},{"name": "Electric cars","code": "cat_2_1"},{"name": "Hybrids","code": "cat_2_2"},{"name": "Sportscars","code": "cat_2_3"}]}]',
            200,
            ['Content-Type' => 'application/json', 'X-Foo' => 'Bar']
        ),
    ]);

    $response = $this->getJson('/product?page=1&page_size=10');

    $response->assertInternalServerError();
});
