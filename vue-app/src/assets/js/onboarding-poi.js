export const onboardingSteps = [
  {
    attachTo: {element: "#addHotspot div div div div"},
    content: {
      title: "POI image",
      description:"You can illustrate your point of interest with an image."
    }
  },
  {
    attachTo: {element: "#addHotspot div.iconHotspots"},
    content: {
      title: "POI icon",
      description:"Choose which icon to display when the visitor gets close to this point of interest."
    }
  },
  {
    attachTo: {element: "#addHotspot div div div div:nth-child(3)"},
    content: {
      title: "POI title",
      description:"Give your point of interest an attractive title."
    }
  },
  {
    attachTo: {element: "#addHotspot > div:nth-child(1) > div:nth-child(1) > div:nth-child(2) > div:nth-child(4)"},
    content: {
      title: "POI description",
      description:"Here is place to add details about this point of interest."
    }
  },
  {
    attachTo: {element: "iframe"},
    content: {
      title: "POI preview",
      description:"On the left side you can preview how your point of interest looks in context."
    },
    on: {
      beforeStep: function () {
        let el=document.querySelector('iframe');
        el.style.pointerEvents="none";
      },
      afterStep: function () {
        let el=document.querySelector('iframe');
        el.style.pointerEvents="";
      },
    },
    options: {popper:{placement: 'right'}}
  },
  {
    attachTo: {element: ".modal-footer button.btn:nth-child(1)"},
    content: {
      title: "Back",
      description:"Click here if you do not wish to save your modifications."
    },
    options: {popper:{placement: 'top'}}
  },
  {
    attachTo: {element: ".modal-footer div .btn"},
    content: {
      title: "Save",
      description:"Click here to save your modifications and return to the 360° photo parameters."
    },
    options: {popper:{placement: 'top'}}
  },
];
