# module-recent-viewed-product-graph-ql
Magento 2 recent viewed products graph ql module. Allow submit recent viewed product ids, get recent viewed product collection

This module require admin enable recent viewed report in admin > stores > configuration > General > Report

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Lof`
 - Enable the module by running `php bin/magento module:enable Lof_RecentViewedGraphQl`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require landofcoder/module-recent-viewed-products-graph-ql`
 - enable the module by running `php bin/magento module:enable Lof_RecentViewedGraphQl`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

## Grqph Ql Queries

1. Query - Get recent viewed products of current logged in customer

Query:

```
{
  recentViewedProducts(search: "", pageSize: 5, currentPage: 1) {
    total_count
    currentPage
    pageSize
    totalPages
    items {
      id
      sku
      uid
      name
      url_key
      price_range {
        minimum_price {
          regular_price {
            value
            currency
          }
        }
      }
      image {
        url
        label
      }
    }
  }
}

```
search: string - search by keyword

2. Mutation - Submit recent viewed product ids of current logged in customer

Query:

```
mutation {
  recentViewedProducts(product_ids: [Int]!) 
  {
    message
    total_count
  }
}
```

product_ids: [Int]! - required field, array of product ids
