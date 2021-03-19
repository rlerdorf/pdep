<?php
require './config.php';

class vizWalker {
    /** @var array */
    private $data = [];
    /** @var string */
    private $pdep = '';
    /** @var string */
    private $cache_file = '';
 
    function __construct(string $pdep, string $cache_file) {
        $this->pdep = $pdep;
        $this->cache_file = $cache_file;
    }

    function loadGraphData():bool {
        $data = json_decode(file_get_contents($this->cache_file), true);
        if (!$data) {
            echo json_last_error_msg();
            return false;
        }
        $this->data = $data;
        return true;
    }

    function getDot(string $node, string $mode = 'class', string $depth = '1', bool $ignore_static = false):string {
        $flags = " -d $depth";
        if ($ignore_static) {
            $flags .= " --ignore-static";
        }
        $flags .= " -g -i " . $this->cache_file;
        if ($mode == 'class') {
            $flags .= ' -c ';
        } elseif ($mode == 'file') {
            $flags .= ' -f ';
        }
        $dot = (string)shell_exec($this->pdep . $flags . escapeshellcmd($node));
        // Make tests yellow
        $dot = preg_replace("#^(.*tests{0,1}/.*)\[shape=box\]#m", '$1[shape=box,style=filled,fillcolor=lightyellow]', $dot);
        return $dot;
    }

    function getDeps(string $node, string $mode = 'class', string $depth = '1', bool $ignore_static = false):string {
        $flags = " -j -d $depth";
        if ($ignore_static) {
            $flags .= " --ignore-static";
        }
        $flags .= " -i " . $this->cache_file;
        if ($mode == 'class') {
            $flags .= ' -c ';
        } elseif ($mode == 'file') {
            $flags .= ' -f ';
        }
        return (string)shell_exec($this->pdep . $flags . escapeshellcmd($node));
    }

    function search(string $str, string $mode):array {
        if (empty($this->data)) {
            $this->loadGraphData();
        }
        $fnc = function($el) use($str) { return (stripos($el, $str) !== false); };
        if ($mode == 'class') {
            return array_keys(array_filter($this->data['ctype'], $fnc, ARRAY_FILTER_USE_KEY));
        } else {
            return array_keys(array_filter($this->data['fgraph'], $fnc, ARRAY_FILTER_USE_KEY));
        }
    }
}

$v = new vizWalker($config['pdep_fullpath'], $config['cached_graph_fullpath']);

if(!empty($_GET['csearch'])) {
    $matches = $v->search((string)$_GET['csearch'], 'class');
    $res = [];
    $i = 0;
    foreach($matches as $val) {
        $res[$i]['id'] = $val;
        $res[$i++]['text'] = trim($val,'\\');
    }
    header("Content-Type: application/json");
    echo json_encode(['results'=>$res]);
    return;
} elseif(!empty($_GET['fsearch'])) {
    $matches = $v->search((string)$_GET['fsearch'], 'file');
    $res = [];
    $i = 0;
    foreach($matches as $val) {
        $res[$i]['id'] = $val;
        $res[$i++]['text'] = $val;
    }
    header("Content-Type: application/json");
    echo json_encode(['results'=>$res]);
    return;
} elseif(!empty($_GET['ajax']) && !empty($_GET['mode']) && !empty($_GET['node']) && !empty($_GET['d'])) {
    $inh = $_GET['inh'] ?? false;
    echo $v->getDot($_GET['node'], $_GET['mode'], $_GET['d'], $inh);
    return;
} elseif(basename($_SERVER['SCRIPT_NAME']) == 'api.php' && !empty($_GET['mode']) && !empty($_GET['node']) && !empty($_GET['d'])) {
    $inh = $_GET['inh'] ?? false;
    echo $v->getDeps($_GET['node'], $_GET['mode'], $_GET['d'], $inh);
    return;
} elseif(!empty($_GET['class'])) {
    $mode = 'class';
    $node = htmlspecialchars($_GET['class']);
} elseif(!empty($_GET['file'])) {
    $mode = 'file';
    $node = htmlspecialchars($_GET['file']);
}
$mode  = $_GET['mode'] ?? 'class';
$depth = $_GET['d'] ?? 1;
$node  = $_GET['node'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <base href="<?=$config['install_uri']?>">

    <title>Code Graph</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.6.2/css/bootstrap-slider.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
  </head>
  <body>
    <div class="container-fluid">

<div class="modal" tabindex="-1" role="dialog" id="infoModal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Etsyweb Class and File Dependencies</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>
        black lines on the graph indicate inheritance<br/>
        <span style="color:#E66100">orange</span> lines are method calls<br/>
        <span style="color:#5D3A9B">solid purple</span> lines indicate a static method call<br/>
        <span style="color:#5D3A9B">dashed purple</span> lines indicate a static constant dependency<br/>
        </p> 
        <p>
        On the file graph, the numbers on the edges indicate the line number the dependency occurs on
        </p>
        <p>
        The 1-5 slider is the dependency depth. For very connected nodes, going above 2 may not work.
        </p>
        <p>
        Nodes on the graph are clickable and will give you the dependency graph for that node
        </p>
        <p>
        You can also paste a class in the file input field in order to get the file-based dependencies
        of that class. Same goes for putting a filename in the class field.
        </p>
        <p>
        Note that rendering is done in the client browser. You may hit stack size issues on graphs with a lot of nodes.
        </p>
      </div>
    </div>
  </div>
</div>
		<div id="controls" class="col-md-4" style="position:relative;">
        <p>
            <br />
            <form id="form1"> 
            <button type="button" class="btn btn-outline-dark btn-sm" data-toggle="modal" data-target="#infoModal" style="position:absolute; top: 1px; right: 1px; font-size: 0.5rem;">Eh?</button>
            <input id="classes" class="form-control" placeholder="Class search" type="text" autocomplete="off"
            <?php 
                if($node && $mode=='class') {
                    echo "value=\"$node\"";
                }
            ?>
            >
            <input id="files" class="form-control" placeholder="Filename search" type="text" autocomplete="off"
            <?php 
                if($node && $mode=='file') {
                    echo "value=\"$node\"";
                }
            ?>
            >
        </p>
        <p>
            <input id="sl1" data-slider-id='depthSlider' type="text" data-slider-min="1" data-slider-max="5" data-slider-step="1" data-slider-value="1"/>
        </p>
            </form>

		</div>
		<div id="graph" class="col-md-12">
        </div>
	</div>
    <script src="https://unpkg.com/jquery@3.4.1/dist/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
    <script src="https://unpkg.com/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>
    <script src="https://unpkg.com/bootstrap-slider@10.6.2/dist/bootstrap-slider.min.js"></script>
    <script src="https://unpkg.com/d3@5.12.0/dist/d3.min.js"></script>
    <script src="https://unpkg.com/viz.js@1.8.2/viz.js" type="javascript/worker"></script>
    <script src="https://unpkg.com/d3-graphviz@2.6.1/build/d3-graphviz.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/xcash/bootstrap-autocomplete@v2.2.2/dist/latest/bootstrap-autocomplete.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        var mode = '<?= $mode?>';
        var node = '<?= addslashes($node)?>';
        var nodes = [];
        var slider = null;
        var transitionSmooth = d3.transition("smooth")
                                 .duration(750)
                                 .ease(d3.easeLinear);

        var graphviz = d3.select("#graph").graphviz()
                         .totalMemory(Math.pow(2,27))
                         .width($("#graph").width())
                         .height($(window).innerHeight())
                         .fit(true)
                         .logEvents(true)
                         .onerror(function(msg) { this.renderDot('graph {"'+msg+'" [shape=none,fontcolor=red] }'); })
                         .transition(function() {
                                       return d3.transition("smooth").duration(800);
                                     });

        function graph(pushState) {
            var d = $('#sl1').val();
            if (typeof graph.numDrawn == 'undefined') {
                graph.numDrawn = 0;
            }
            $(".slider-handle").addClass("spinner-grow");
            console.log("Graphing " + node);

            fetch(`?ajax=1&mode=`+mode+`&node=`+node+`&d=`+d)
            .then(function(response) {
                return response.text();
            })
            .then(function(text) {
                if (text) {
                    if(pushState) {
                        if(graph.numDrawn > 0) {
                            console.log("Pushing state" + '?mode='+mode+'&node='+node+'&d='+d);
                            history.pushState({mode:mode,node:node,d:d},'','?mode='+mode+'&node='+node+'&d='+d);
                        } else {
                            console.log("Replacing state" + '?mode='+mode+'&node='+node+'&d='+d);
                            history.replaceState({mode:mode,node:node,d:d},'','?mode='+mode+'&node='+node+'&d='+d);
                        }
                    }
                    try {
                        graphviz.renderDot(text);
                    } catch(e) {
                        console.log(e);
                        $(".slider-handle").removeClass("spinner-grow");
                    }
                    graphviz.on('end', function() {
                        nodes = d3.selectAll('.node');
                        graph.numDrawn++;
                        $(".slider-handle").removeClass("spinner-grow");
                        nodes.on("click", function () {
                            node = d3.select(this).selectAll('text').text();
                            if(mode=='class') {
                                $("#classes").val(node);
                            } else {
                                $("#files").val(node);
                            }
                            graph(true);
                            return false;
                        });
                    });
                } else {
                    $(".slider-handle").removeClass("spinner-grow");
                }
            });
            return false;
        }

        $("#sl1").on("change", function() { graph(true); return false; });

        $("#classes").on("autocomplete.select", function(evt, el) { mode = 'class'; node = el.id; graph(true); return false; });
        $("#classes").on("autocomplete.freevalue", function(evt, val) { mode = 'class'; node = val; graph(true); return false; });
        $('#classes').autoComplete({
            resolver: 'custom',
            formatResult: function (el) {
                              return {
                                  value: el.id,
                                  text: el.text
                              };
                          },
            events: {
                search: function (q, cb) {
                            $.ajax('', { data: { 'csearch': q} }).done(function (res) {
								cb(res.results)
							});
						}
					}
        });

        $("#files").on("autocomplete.select", function(evt, el) { mode = 'file'; node = el.id; graph(true); return false; });
        $("#files").on("autocomplete.freevalue", function(evt, val) { mode = 'file'; node = val; graph(true); return false; });
        $('#files').autoComplete({
            resolver: 'custom',
            formatResult: function (el) {
                              return {
                                  value: el.id,
                                  text: el.text
                              };
                          },
            events: {
                search: function (q, cb) {
                            $.ajax('', { data: { 'fsearch': q} }).done(function (res) {
								cb(res.results)
							});
						}
					}
        });

        $(document).ready(function() {
            $('#sl1').slider({
                tooltip: 'hide',
                value: <?=$depth?>,
                ticks: [1, 2, 3, 4, 5],
                ticks_labels: ['1', '2', '3', '4', '5'],
            });

            $('#form1').on('click', '.dropdown-item', function () {
                return false;
            });

            if (node.length) {
                graph(true);
            }
        });

        $(window).bind("popstate", function(e) {
            var state = e.originalEvent.state;
            if (state !== null) { 
                $('#sl1').slider('setValue', state.d);
                mode = state.mode;
                node = state.node;
                if(mode=='class') {
                    $("#classes").val(node);
                } else {
                    $("#files").val(node);
                }
                graph(false);
            }
            return true;
        });
    </script>
  </body>
</html>
