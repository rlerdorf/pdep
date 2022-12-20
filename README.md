# pdep
pdep frontend to Phan's DependencyGraphPlugin

Follow the [Phan](https://github.com/phan/phan) instructions for setting up Phan for your project.
Add the `.phan/pdep_config.php` to your project's `.phan` directory. The `pdep_config.php` file is basically a modified version of `.phan/config.php`. You can simply run `cp ./.phan/config.php ./.phan/pdep_config.php` to use your Phan config.
Then generate the graph json file with `vendor/phan/phan/tool/pdep -j > graph.json`
And edit the `config.php` file.
