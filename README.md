# PimcoreRadBrickBundle
A RAD way to create Areabricks in Pimcore

[![Coverage Status](https://coveralls.io/repos/github/khusseini/PimcoreRadBrickBundle/badge.svg?branch=1.x)](https://coveralls.io/github/khusseini/PimcoreRadBrickBundle?branch=1.x)

## Purpose

Configure data and editables available in Pimcore Areabricks view and edit templates.

## Installation
```
composer require khusseini/pimcore-radbrick
```


## Usage

Simply enable the Bundle, test out this configuration in your `config.yml` and start creating templates.

```yaml
pimcore_rad_brick:
  areabricks:
    my_wysiwyg:
      label: WYSIWYG // Label to use in admin UI
      icon:  ~ // Path to icon in admin UI
      open: ~ // Set the open html
      close: ~ // Set the close html
      use_edit: false // Use edit.html.twig
      class: ~ // Use an existing symfony service
      editables:
        wysiwyg_content:
          type: wysiwyg
          options: [] // you can pass here any options accepted by the editable
```

Now create a template as usual in `views/Areas/my_wysiwyg/view.html.twig`:
```twig
{# add autogenerated meta description #}
{% do pimcore_head_meta().setDescription(wysiwyg_content.getData(), 160) %}
<div class="content">
  {% pimcoreglossary %}
    {{ wysiwyg_content|raw }}
  {% endpimcoreglossary %}
</div>
```


### Creating multiple instances

In order to create multiple instances of an editable with the same configuraiton,
it is as easy as adding the `instances` attribute:

`config.yml`
```yml
pimcore_rad_brick:
  areabricks:
    my_wysiwyg:
      label: WYSIWYG
      editables:
        wysiwyg_content:
          instances: 3
          type: wysiwyg
          options: [] // you can pass here any options accepted by the editable
```

The instance variables are created using the basename of the editable and postfixing it with either the provided ids in `instance_ids` or by simply using the array index.

```twig
{% for wysiwyg_instance in wysiwyg_content %}
  {{ wysiwyg_instance|raw }}
{% endfor %}
```


#### Making instances configurable

In most cases, it doesn't make much sense to hardcode the number of instances
of an editable. In order to make the number of instances configurable via admin
we will leverage the power of the [Expression Language Component](https://symfony.com/doc/current/components/expression_language.html).

```yml
pimcore_rad_brick:
  areabricks:
    my_wysiwyg:
      label: WYSIWYG
      use_edit: true // Note that we use an edit template to configure the instances
      editables:
        num_editors:
          type: select
          options:
            store: [1,2,5]
        wysiwyg_content:
          instances: view.get("num_editors").getData()
          type: wysiwyg
```

NOTE: The expression context contains the following objects:
- `request`: The current `Request` object
- `view`: The current `ViewModel`
- `datasources`: The `DatasourceRegistry`


#### Practical example

A more practical example is an integration into the bootstrap grid.
Two areabricks can be easily created in order to support the bootstrap grid.

```yaml
pimcore_rad_brick:
  areabricks:
    container:
      label: Container
      use_edit: true
      editables:
        container_css_class:
          type: input
        container_area_block:
          type: areablock
          options:
            allowed:
            - columns
            params:
              forceEditInView: true
    columns:
      label: Columns
      use_edit: true
      editables:
        num_columns:
          type: select
          options:
            store: [1, 2, 3, 4, 5, 6]
            defaultValue: 1
        column_area_block:
          instances: view.get("num_columns").getData()
          type: areablock
          options:
            params:
              forceEditInView: true
```

`container/view.html.twig`:
```twig
<div class="container">
  {{ container_area_block|raw }}
</div>
```

`columns/edit.html.twig`:
```twig
# of Columns: {{ num_columns|raw }}
```

`columns/view.html.twig`:
```twig
{% set col_width = 12 / num_columns.getData() %}
<div class="row">
  {% for column in column_area_block) %}
  <div class="col-{{ col_width }}">
      {{ column|raw }}
  </div>
  {% else %}
  <div class="col-{{ col_width }}">
      {{ column_area_block|raw }}
  </div>
  {% endfor %}
</div>
```


### Using groups

You might have noticed the strange `else` case in the above example.
This is due to how the `instances` work. If only one instance is configured,
then the variable is presented in the view as an editable, not an array.

In order to keep instances in an array or group, you can use the `groups`
and `group` configurations to provide templates to editables.

`config.yml`:
```yml
areabricks:
  promo_box:
    label: Promo Box
    use_edit: true
    groups:
      boxes:
        instances: 'view["num_boxes"].getValue() ? : 2'
    editables:
      num_boxes:
        type: select
        options:
          store: [2, 3, 5]
      link:
        type: link
        group: boxes
      image:
        type: image
        group: boxes
```

`promo_box/edit.html.twig`:
```twig
# of Boxes: {{ num_boxes|raw }}
```

`view.html.twig`:
```twig
<div>
{% for box in boxes %}
  {{ box.link|raw }}
  {{ box.image|raw }}
{% endfor %}
</div>
```


### Using maps

At times an editable in your areabrick depends on some configuration value.
The `map` configuration node may be used to map data between editables.

For example an image areabrick may be configured to use a specific thumbnail:

`edit.html.twig`
```twig
Thumbnail: {{ image_thumbnail|raw }}
```

`view.html.twig`
```twig
<div class="image-container">
{{ image_content|raw }}
</div>
```

`config.yml`
```yml
pimcore_rad_brick:
  areabricks:
    image:
      label: Image
      editables:
        image_thumbnail:
          type: input
        image_content:
          type: image
          map:
          - source: '[image_thumbnail].data'
            target: '[options][thumbnail]'
```

The `source` and `target` properties uses the [Symfony Property Access Component](https://symfony.com/doc/current/components/property_access.html) to fetch and insert data.

The above mapping example would fetch the `data` property's value from `image_thumbnail` editable (resides in ViewModel, hence the array notation) and insert right into the config tree in `[pimcore_rad_brick][areabricks][image][editables][image_content][options][thumbnail]`


### Using Datasources

Datasources simplify the creation of widgets that get their data from other services. For example, in CoreShop one might want to show a slider that contains the products of a certain category.

`config.yml`
```yml
pimcore_rad_brick:
  ## Define data sources to be used by areabricks
  datasources:
    products_by_category:
      service_id: 'app.repository.product'
      method: 'findByCategory'
      args:
      - '[category]' ## Specify which data to pass. The input array is passed by areabricks.
  areabricks:
    category_slider:
      label: Category Slider
      editables:
        select_category:
          type: relation
          options:
            types: ['object']
            subtypes:
              object: ['object']
            classes: ['CoreShopCategory']

      datasources: ## Datasource configuration for this areabrick
        products:
          id: products_by_category
          args:
            category: 'view["category"].getElement()' ## Define category argument (available in input array to the datasource above)
```
The property path of an input argument for a datasource contains following information:
- `request`: Access to the current request object
- `view`: Access to elements in the viewmodel

`edit.html.twig`
```twig
Category: {{ select_category|raw }}
```
`view.html.twig`
```twig
<div class="slider">
  {% for product in products %}
  <div class="item">{% include 'product-tile.tml.twig' with {product: product} only %}</div>
</div>
```


#### Connecting Editables and datasources

When using datasources, it is also possible to connect editables to items in datasources.
For example product teasers could have a tagline which needs to be added manually,
but all other fields are filled by the product. The following configuration can solve this issue:

```yml
pimcore_rad_brick:
  datasources:
    products_by_category:
      service_id: 'app.repository.product'
      method: 'findByCategory'
      args:
      - '[category]' ## Specify which data to pass. The input array is passed by areabricks.
  areabricks:
    category_slider:
      label: Category Slider
      use_edit: true
      editables:
        select_category:
          type: relation
          options:
            types: ['object']
            subtypes:
              object: ['object']
            classes: ['CoreShopCategory']
        product_title:
          type: input
          datasource:
            name: products
            id: item.getId() # Specify ID source (will be casted to string)

      datasources:
        products:
          id: products_by_category
          args:
            category: view["select_category"].getElement()
```


## Contribute

If you find something that is missing which there is no issue for or want to help fix a bug or documentation, please feel free to submit a PR.
Read through the code and try to run the tests with `vendor/bin/phpunit`
Coding standard is `@PSR2` and `php-cs-fixer` will be used to format the code. Scrutinizer will be used in future for enforcing coding standards.


## Donations

<a href="https://www.buymeacoffee.com/5sAwkYgyb" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/arial-white.png" alt="Buy Me A Coffee" style="height: 51px !important;width: 217px !important;" ></a>
