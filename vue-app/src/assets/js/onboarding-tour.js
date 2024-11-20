export const onboardingSteps = [
  {
    attachTo: {element: "a.home"},
    content: {
      title: "Home button",
      description:"Click here to return to your tour collection."
    }
  },
  {
    attachTo: {element: "a.edit"},
    content: {
      title: "Edit button",
      description:"Click here to modify the parameters of your tour: title, preview image, description..."
    }
  },
  {
    attachTo: {element: "div.list a.btn.big"},
    content: {
      title: "Add button",
      description:"Click here to add a 360° photo to your tour."
    },
    options: {popper:{placement: 'right'}}
  },
  {
    attachTo: {element: "div.list"},
    content: {
      title: "The 360° photos",
      description:"All added 360° photos will appear here. Use them to build your tour."
    },
    options: {popper:{placement: 'right'}}
  },
  {
    attachTo: {element: "input.search"},
    content: {
      title: "Search field",
      description:"If properly named, you can search for the right 360° photo. Quite useful when there are many."
    },
    options: {popper:{placement: 'right'}}
  },
  {
    attachTo: {element: "li.edit"},
    content: {
      title: "Edit",
      description:"This opens the parameters of the 360° photo: modify the name, replace the photo, and add points of interest."
    },
    on: {
      beforeStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=false;
        btn.show();
        document.querySelector('li.edit').classList.add("active");
      },
      afterStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=true;
        btn.hide();
        document.querySelector('li.edit').classList.remove("active");
      },
    },
    options: {
      popper:{placement: 'right'},
      overlay: {padding: 4}
    }
  },
  {
    attachTo: {element: "li.add_hotspot"},
    content: {
      title: "Add point of interest",
      description:"Click here to quickly add or remove points of interest."
    },
    on: {
      beforeStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=false;
        btn.show();
        document.querySelector('li.add_hotspot').classList.add("active");
      },
      afterStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=true;
        btn.hide();
        document.querySelector('li.add_hotspot').classList.remove("active");
      },
    },
    options: {
      popper:{placement: 'right'},
      overlay: {padding: 4}
    }
  },
  {
    attachTo: {element: "li.delete"},
    content: {
      title: "Delete",
      description:"Clicking here removes the 360° photo and its points of interest."
    },
    on: {
      beforeStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=false;
        btn.show();
        document.querySelector('li.delete').classList.add("active");
      },
      afterStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=true;
        btn.hide();
        document.querySelector('li.delete').classList.remove("active");
      },
    },
    options: {
      popper:{placement: 'right'},
      overlay: {padding: 4}
    }
  },
  {
    attachTo: {element: ".playground .dropzone"},
    content: {
      title: "View A / View B",
      description:"These two 360° viewers are essentiel for building your tour. Drag and drop a different 360° photo from the left-side library into view A or view B."
    }
  },
  {
    attachTo: {element: ".playground .dropzone"},
    content: {
      title: "Align views",
      description:"<video src='/video/link_view.mp4' width='460' autoplay loop>Video file not supported</video> <p>Rotate the views of the two photos so that they face each other. Then click the button to link the two views. This creates a target point that will enable jumping from one photo to the other during the tour.</p>",
      html: true
    }
  },
  {
    attachTo: {element: "#graph"},
    content: {
      title: "The graph",
      description:"The structure of your 360° tour is shown in this graph. Each dot represents a placed 360° photo from your library. The line between two photos represents their link. Click on a line to view the two linked 360° photos in the viewer."
    }
  },
  {
    attachTo: {element: ".pic_box"},
    content: {
      title: "360° photo",
      description:"When you hover over the photo, you will see it highlighted in the graph on the right."
    },
    options: {popper:{placement: 'right'}}
  },
  {
    attachTo: {element: "li.pin"},
    content: {
      title: "Starting point",
      description:"A tour needs to start somewhere. Pin the 360° photo from which your tour begins."
    },
    on: {
      beforeStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=false;
        btn.show();
        document.querySelector('li.pin').classList.add("active");
      },
      afterStep: function () {
        let el = document.querySelector('.btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=true;
        btn.hide();
        document.querySelector('li.pin').classList.remove("active");
      },
    },
    options: {
      popper:{placement: 'right'},
      overlay: {padding: 4}
    }
  },
  {
    attachTo: {element: "ul.right"},
    content: {
      title: "Launch the tour",
      description:"Once your 360 tour is ready, you can bring it to life through these three options."
    }
  },
  {
    attachTo: {element: ".pic_box"},
    content: {
      title: "Let's add points of interest!",
      description:"You have seen the essentials of linking your 360° photos. Now you will see how to add points of interest."
    }
  },
];
