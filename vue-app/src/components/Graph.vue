<script setup>
  import { ref, onMounted, onUpdated } from 'vue'
  import cytoscape from 'cytoscape';
  import klay from 'cytoscape-klay';
  cytoscape.use( klay );
  const props = defineProps(['spotHasSpots', 'spots', 'startId']);
  const emit = defineEmits(['hover', 'click' ]);
  var cy;
  const cyDiv = ref(null)
  const spotHover = ref(null);

  /*
   * Place the spot in the graph
   */
  function center(id){
  }

  const cyLayoutOptions = {
    name: 'klay',
    nodeDimensionsIncludeLabels: false, // Boolean which changes whether label dimensions are included when calculating node dimensions
    fit: true, // Whether to fit
    padding: 15, // Padding on fit
    animate: true, // Whether to transition the node positions
    animateFilter: function( node, i ){ return true; }, // Whether to animate specific nodes when animation is on; non-animated nodes immediately go to their final positions
    animationDuration: 500, // Duration of animation in ms if enabled
    animationEasing: undefined, // Easing of animation if enabled
    transform: function( node, pos ){ return pos; }, // A function that applies a transform to the final node position
    ready: undefined, // Callback on layoutready
    stop: undefined, // Callback on layoutstop
    klay: {
      // Following descriptions taken from http://layout.rtsys.informatik.uni-kiel.de:9444/Providedlayout.html?algorithm=de.cau.cs.kieler.klay.layered
      addUnnecessaryBendpoints: false, // Adds bend points even if an edge does not change direction.
      aspectRatio: 1.6, // The aimed aspect ratio of the drawing, that is the quotient of width by height
      borderSpacing: 20, // Minimal amount of space to be left to the border
      compactComponents: false, // Tries to further compact components (disconnected sub-graphs).
      crossingMinimization: 'LAYER_SWEEP', // Strategy for crossing minimization.

      cycleBreaking: 'GREEDY', // Strategy for cycle breaking. Cycle breaking looks for cycles in the graph and determines which edges to reverse to break the cycles. Reversed edges will end up pointing to the opposite direction of regular edges (that is, reversed edges will point left if edges usually point right).
      /* GREEDY This algorithm reverses edges greedily. The algorithm tries to avoid edges that have the Priority property set.
      INTERACTIVE The interactive algorithm tries to reverse edges that already pointed leftwards in the input graph. This requires node and port coordinates to have been set to sensible values.*/
      direction: 'UNDEFINED', // Overall direction of edges: horizontal (right / left) or vertical (down / up)
      /* UNDEFINED, RIGHT, LEFT, DOWN, UP */
      edgeRouting: 'ORTHOGONAL', // Defines how edges are routed (POLYLINE, ORTHOGONAL, SPLINES)
      edgeSpacingFactor: 0.5, // Factor by which the object spacing is multiplied to arrive at the minimal spacing between edges.
      feedbackEdges: false, // Whether feedback edges should be highlighted by routing around the nodes.
      fixedAlignment: 'NONE', // Tells the BK node placer to use a certain alignment instead of taking the optimal result.  This option should usually be left alone.
      /* NONE Chooses the smallest layout from the four possible candidates.
      LEFTUP Chooses the left-up candidate from the four possible candidates.
      RIGHTUP Chooses the right-up candidate from the four possible candidates.
      LEFTDOWN Chooses the left-down candidate from the four possible candidates.
      RIGHTDOWN Chooses the right-down candidate from the four possible candidates.
      BALANCED Creates a balanced layout from the four possible candidates. */
      inLayerSpacingFactor: 1.0, // Factor by which the usual spacing is multiplied to determine the in-layer spacing between objects.
      layoutHierarchy: false, // Whether the selected layouter should consider the full hierarchy
      linearSegmentsDeflectionDampening: 0.3, // Dampens the movement of nodes to keep the diagram from getting too large.
      mergeEdges: false, // Edges that have no ports are merged so they touch the connected nodes at the same points.
      mergeHierarchyCrossingEdges: true, // If hierarchical layout is active, hierarchy-crossing edges use as few hierarchical ports as possible.
      nodeLayering:'NETWORK_SIMPLEX', // Strategy for node layering.
      /* NETWORK_SIMPLEX This algorithm tries to minimize the length of edges. This is the most computationally intensive algorithm. The number of iterations after which it aborts if it hasn't found a result yet can be set with the Maximal Iterations option.
      LONGEST_PATH A very simple algorithm that distributes nodes along their longest path to a sink node.
      INTERACTIVE Distributes the nodes into layers by comparing their positions before the layout algorithm was started. The idea is that the relative horizontal order of nodes as it was before layout was applied is not changed. This of course requires valid positions for all nodes to have been set on the input graph before calling the layout algorithm. The interactive node layering algorithm uses the Interactive Reference Point option to determine which reference point of nodes are used to compare positions. */
      nodePlacement:'BRANDES_KOEPF', // Strategy for Node Placement
      /* BRANDES_KOEPF Minimizes the number of edge bends at the expense of diagram size: diagrams drawn with this algorithm are usually higher than diagrams drawn with other algorithms.
      LINEAR_SEGMENTS Computes a balanced placement.
      INTERACTIVE Tries to keep the preset y coordinates of nodes from the original layout. For dummy nodes, a guess is made to infer their coordinates. Requires the other interactive phase implementations to have run as well.
      SIMPLE Minimizes the area at the expense of... well, pretty much everything else. */
      randomizationSeed: 1, // Seed used for pseudo-random number generators to control the layout algorithm; 0 means a new seed is generated
      routeSelfLoopInside: false, // Whether a self-loop is routed around or inside its node.
      separateConnectedComponents: true, // Whether each connected component should be processed separately
      spacing: 85, // Overall setting for the minimal amount of space to be left between objects
      thoroughness: 7 // How much effort should be spent to produce a nice layout..
    }
  };

  /*
   * Initializes the graph
   */
  function cyInit(){
    cy = cytoscape({
      container: cyDiv.value, // container to render in
      wheelSensitivity: 0.2,
      maxZoom:1,
      minZoom: 1,
      pixelRatio: 1.5,
      style: [ // the stylesheet for the graph
        {
          selector: 'node',
          css: {
            //~ 'background-color': '#f1bb45',
            'background-color': '#fff',
            'background-opacity': 0,
            'border-width' : 0,
            'label': 'data(title)',
            'font-size': 15,
            'text-valign': 'bottom',
            'text-halign': 'center',
            'color': '#000',
            'label': 'data(title)',
            'width': 0,
            'height': 0,
            'margin': 3,
            'padding':4
          }
        },

        {
          selector: 'node.position',
          css: {
            'border-width' : 2,
            'border-color' : '#f1bb45',
            'background-color': '#f1bb45',
            'background-opacity': 1,
            'label': 'data(title)',
            'font-size': 15,
            'font-weight': 'bold',
            'text-valign': 'center',
            'text-halign': 'center',
            'color': '#fff',
            'width': 20,
            'height': 20,
            'border-width' : 2,
            'border-color' : '#FAFAFA',
          }
        },

        {
          selector: 'node.position.start',
          css: {
            'border-width' : 2,
            'border-color' : '#ee521a',
            'color': 'white',
            'background-color': '#ee521a',
          }
        },

        {
          selector: 'node.position.highlight',
          css: {
            'border-color' : '#555555',
          }
        },

        {
          selector: 'node.position.hover',
          css: {
            'width': 20,
            'height': 20,
            'border-width' : 2,
            'border-color' : '#7f7f7f',
          }
        },

        {
          selector: 'edge',
          css: {
            'width': 2,
            'line-color': '#999',
            'target-arrow-shape': 'none',
          }
        },

        {
          selector: 'edge.hover',
          css: {
            'width': 3,
            'line-color': '#f1bb45',
            'cursor': 'wait',
          }
        }
      ],
    });
  }

  /*
   * Emit a click event containing each node connected to the edge
   */
  async function clickEdge(id){
    let shs = props.spotHasSpots.filter((shs) => shs.id == id);
    if (shs.length > 0) {
      emit('click', {spots: [
        {
          id: shs[0].spot1,
          y: shs[0].spot1y
        },
        {
          id: shs[0].spot2,
          y: shs[0].spot2y
        },
      ]});
    }
  }

  function clickNode(id){
    emit('click', {spots: [
      { id: parseInt(id) }
    ]});
  }

  /*
   * Adds or removes nodes to the graph from shs
   */
  function update(){
    console.debug("Update Graph component");
    const titleLimit = 15;
    let center = {x: (cy.extent().x1 + cy.extent().x2)/2, y: (cy.extent().y1 + cy.extent().y2)/2};
    // Add new nodes to the graph
    props.spotHasSpots.forEach(function(shs){
      if (cy.elements("edge[id = 'e" + shs.id + "']").length == 0){
        let spot1 =props.spots.filter((spot) => spot.id == shs.spot1)[0];
        let spot2 =props.spots.filter((spot) => spot.id == shs.spot2)[0];
        // Add 'start' class to the starting node
        // NOTICE: we modify these classes in the setStartId method too
        let spot1Classes = spot1.id == props.startId ? ['position', 'start'] : ['position']
        let spot2Classes = spot2.id == props.startId ? ['position', 'start'] : ['position']
        cy.add([
          // spot1 title
          { group: 'nodes',
            data: {
              id: 'n' + shs.spot1,
              title: spot1.title.length < titleLimit ? spot1.title : spot1.title.substring(0,titleLimit-2) + "…"
            },
            position: {
              x: center.x,
              y: center.y
            },
            classes: ['top-center']
          },
          // spot1 position
          { group: 'nodes',
            data: {
              id: 't' + shs.spot1,
              title: spot1.position,
              parent: 'n' + shs.spot1,
              type: "position",
            },
            position: {
              x: center.x,
              y: center.y
            },
            classes: spot1Classes
          },
          // spot2 title
          { group: 'nodes',
            data: {
              id: 'n' + shs.spot2,
              title: spot2.title.length < titleLimit ? spot2.title : spot2.title.substring(0,titleLimit-2) + "…"
            },
            position: {
              x: center.x,
              y: center.y
            },
            classes: ['top-center']
          },
          // spot2 position
          { group: 'nodes',
            data: {
              id: 't' + shs.spot2,
              title: spot2.position,
              parent: 'n' + shs.spot2,
              type: "position",
            },
            position: {
              x: center.x,
              y: center.y
            },
            classes: spot2Classes
          },
          // edge
          { group: 'edges',
            data: {
              id: "e" + shs.id,
              source: 'n' + shs.spot1,
              target: 'n' + shs.spot2
            }
          }
        ]);
      }
    });

    // Remove deleted edges from the graph
    let checkNodes = []; //Nodes to check later
    let shsIds = props.spotHasSpots.map((shs) => shs.id);
    let edges = cy.elements("edge");
    edges.forEach(function(edge){
      if (!shsIds.includes(parseInt(edge.id().substring(1)))){
        checkNodes.push(edge.connectedNodes());
        cy.remove(edge);
      }
    });

    // Remove unconnected nodes
    checkNodes.forEach(function(nodes){
      nodes.forEach(function(node){
        if (node.connectedEdges().length == 0){
          cy.remove(node);
        }
      });
    });

    cy.layout(cyLayoutOptions).run();

    // Add the events on the graph elements
    cy.edges().on('click', function(e) {
      e.preventDefault();
      clickEdge(e.target.id().substring(1));
    });
    cy.edges().on('mouseover', function(e) {
      e.target.addClass("hover");
      cyDiv.value.style.cursor="pointer";
    });
    cy.edges().on('mouseout', function(e) {
      e.target.removeClass("hover");
      cyDiv.value.style.cursor="";
    });
    cy.elements('node[type = "position"]').on('mouseover', function(e) {
      e.target.addClass("hover");
      cyDiv.value.style.cursor="pointer";
      // Add the hover class on the thumbnail
      spotHover.value = e.target.id().substring(1);
    });
    cy.elements('node[type = "position"]').on('mouseout', function(e) {
      e.target.removeClass("hover");
      cyDiv.value.style.cursor="";
      // remove the hover class on the thumbnail
      spotHover.value = null;
    });
    cy.nodes().on('click', function(e) {
      e.preventDefault();
      clickNode(e.target.id().substring(1));
    });

  }

  function highlightNode(id){
    if(!cy) return;
    let selector = "node[id = 't" + id + "']";
    let node = cy.elements(selector);
    node.addClass('highlight');

    // Number of pixels between the node and the viewport border
    let padding_x = 50;
    let padding_y = 20;

    // Duration of the animation
    let duration = 200;
    padding_x = padding_x * cy.zoom();
    padding_y = padding_y * cy.zoom();

    // Let's place the node into the viewport
    cy.stop(true);
    let pos = node.renderedPosition();
    let width = cy.width();
    let height = cy.height();
    let panx, pany;
    if (!pos) return;
    if (pos.x < padding_x) {
      panx = -(pos.x) + padding_x;
    } else if (pos.x > width - padding_x) {
      panx = width-pos.x - padding_x;
    }
    if (pos.y < padding_y) {
      pany = -(pos.y) + padding_y;
    } else if (pos.y > height - padding_y) {
      pany = height-pos.y - padding_y;
    }
    if (panx || pany){
      cy.animate({panBy: {x: panx, y: pany}}, {duration: duration, easing: 'ease'});
    }
  }

  function unHighlightNode(id){
    let selector = "node[id = 't" + id + "']";
    let node = cy.elements(selector);
    node.removeClass('highlight');
  }

  function setStartId(spot_id){
    cy.elements("node.start").removeClass("start");
    let newNode = cy.elements("node[id = 't" + spot_id + "']");
    // New node not found?
    if (newNode.length == 0){
      return;
    }
    newNode[0].addClass('start');
  }

  onMounted(() => {
    cyInit();
  });

  onUpdated(() => {
    update();
  });

  defineExpose({
    highlightNode,
    unHighlightNode,
    update,
    setStartId
  })

</script>

<template>
  <div class="bottom" ref="cyDiv">
  </div>
</template>

<style scoped>

</style>
