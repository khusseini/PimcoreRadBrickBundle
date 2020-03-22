# CHANGELOG


## v1.1.0:


### Features:
- [#2 Grouped editables](https://github.com/khusseini/PimcoreRadBrickBundle/issues/2)


### Added:

- `Renderer`: Class to store `RenderArgument`s during editable creation. This allows configurator to access previously generated `RenderArgument`s.
- `GroupConfigurator`: Class to group editables into one view variable.


### Changed:

- Refactored `IConfigurator` to accomodate `Renderer`
