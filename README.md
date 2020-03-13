# PimcoreRadBrickBundle
A RAD way to create Areabricks in Pimcore

Many areabricks do not need any PHP functionality in order to be 
configured or shown and most of the work is in the templates.
The RadBrickBundle was created to simplify this process by
making Areabricks configurable.

## Installation
```
composer require khusseini/pimcore-radbrick
```

## Usage

Simply enable the Bundle and test out this configuration in your `config.yml` and start creating templates.

```yaml
pimcore_rad_brick:
  areabricks:
    my_wysiwyg: 
      label: WYSIWYG
      editables:
        wysiwyg_content:
          type: wysiwyg
          options: []
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

### Using sources

Sometimes areabricks need to be configurable to have more than one instance. For example a teaser could display 1, 2 or 3 items.
A configuration for this could look like:

```yaml
pimcore_rad_brick:
  areabricks:
    teaser:
      label: Teaser
      use_edit: true
      editables:
        num_items:
          type: select
          options:
            store: [1, 2, 3]
            defaultValue: 1
        teaser_area_block:
          type: areablock
          source: num_columns
          options:
            params:
              forceEditInView: true
```

This simplifies templates to look like this:

`edit.html.twig`
```twig
Items: {{ num_items|raw }}
```

`view.html.twig`
```twig
{% for i in range(1, num_items) %}
{{ teaser_area_block[i]|raw }}
{% endfor %}
```
---
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
            - hero_slider
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
          type: areablock
          source: num_columns
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

`columns/view.html.twig`:
```twig
{% set col_width = 12 / num_columns.getData() %}
<div class="row">
  {% for i in range(1, num_columns.getData()) %}
  <div class="col-{{ col_width }}">
      {{ column_area_block[i]|raw }}
  </div>
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