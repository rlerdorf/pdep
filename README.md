# pdep

This project is a frontend interface for [Phan](https://github.com/phan/phan)  that allows you to visualize class and file dependencies in your PHP projects.

## Prerequisites

Before you begin, ensure you have the following tools installed

* **Phan** for static analysis of your PHP codebase.
* **Composer** for managing PHP dependencies.

## Setup Instructions

### 1. Set Up Phan

* Follow the **Phan documentation** to set up Phan in your project.

* Add the  file to your  directory. This file should contain any necessary configurations for generating the dependency graph. `pdep_config.php.phan`

### 2. Generate the Graph JSON File

Generate the graph JSON file using the following command

```sh
vendor/phan/phan/tool/pdep -j > graph.json
```

This command will analyze your project and output a  file, which contains the dependency graph data. `graph.json`

### 3. Configure the Frontend

Open the  file in the pdep frontend directory. `config.php`

Edit the configuration to match your project setup

```sh
$config = [
    'install_uri' => '/your-path/', 

    'pdep_fullpath' => '/var/www/your-project/vendor/phan/phan/tool/pdep', 

    'cached_graph_fullpath' => '/var/www/your-project/graph.json' 
]
```

### 4. Run the Application

Start a local PHP server to serve the frontend

```sh
php -S localhost:8000
```

Visit  in your browser to view the dependency graph of your project. http://localhost:8000
