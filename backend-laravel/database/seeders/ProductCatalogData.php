<?php

namespace Database\Seeders;

class ProductCatalogData
{
    public static function get(): array
    {
        return array_merge(
            ProductCatalogDefinitionPart1::get(),
            ProductCatalogDefinitionPart2::get(),
            ProductCatalogDefinitionPart3::get(),
            ProductCatalogDefinitionPart4::get()
        );
    }
}
