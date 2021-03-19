# pdep
pdep frontend to Phan's DependencyGraphPlugin

Follow the [Phan](https://github.com/phan/phan) instructions for setting up Phan for your project.
Add the `.phan/pdep_config.php` to your project's `.phan` directory.
Then generate the graph json file with `vendor/phan/phan/tool/pdep -j > graph.json`
And edit the `config.php` file.
