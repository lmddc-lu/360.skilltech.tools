export const onboardingSteps = [
  {
    attachTo: {element: '.tour_row' },
    content: {
      title: "Your collection",
      description:"All your 360 tours are listed here."
    },
    options: {popper:{placement: 'bottom'}}
  },
  {
    attachTo: {element: "#btn-create-tour"},
    content: {
      title: "Create a new tour",
      description:"Here is where you begin to create your 360 tour."
    },
  },
  {
    attachTo: {element: ".tour.demo"},
    content: {
      title: "A demo tour",
      description:"We added this example 360 tour to your collection. Feel free to modify or delete it."
    }
  },
  {
    attachTo: {element: ".tour.demo ul"},
    content: {
      title: "Tour menu",
      description:"This drop-down menu lets you edit, share, download, or delete a tour."
    },
    on: {
      beforeStep: function () {
        let el = document.querySelector('.tour.demo .btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=false;
        btn.show();
      },
      afterStep: function () {
        let el = document.querySelector('.tour.demo .btn.btn-secondary.btn-sm');
        let btn = bootstrap.Dropdown.getOrCreateInstance(el);
        btn._config.autoClose=true;
        btn.hide();
      },
    },
    options: {popper:{placement: 'right'}}
  },
  {
    attachTo: {element: ".tour.demo .thumbnail"},
    content: {
      title: "Take a closer look",
      description:"You will now explore this example 360 tour."
    }
  },
];
