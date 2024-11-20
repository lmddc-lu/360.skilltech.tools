export const onboardingSteps = [
  {
    attachTo: {element: ".input_box input"},
    content: {
      title: "Naming",
      description:"Choose a name to easily find your 360° photo in your project library."
    }
  },
  {
    attachTo: {element: ".image"},
    content: {
      title: "360° photo",
      description:"This is the preview of your 360° photo. You can replace it with another. The links and points of interest remain."
    }
  },
  {
    attachTo: {element: "iframe"},
    content: {
      title: "360 viewer",
      description:"This viewer allows you to rotate your 360° photo and choose a position for your point of interest by clicking on the spot in the photo."
    },
    on: {
      beforeStep: function () {
        console.log("if bef");
        let el=document.querySelector('iframe');
        el.style.pointerEvents="none";
      },
      afterStep: function () {
        console.log("if af");
        let el=document.querySelector('iframe');
        el.style.pointerEvents="";
      },
    },
    options: {popper:{placement: 'right'}}
  },
  {
    attachTo: {element: ".listHotspots"},
    content: {
      title: "Points of interest",
      description:"All points of interest on this 360° photo are listed here. Click on the list item to preview the point of interest."
    },
  },
  {
    attachTo: {element: "img.delete"},
    content: {
      title: "Delete point of interest",
      description:"Clicking here will remove the POI."
    }
  },
  {
    attachTo: {element: "img.edit"},
    content: {
      title: "Edit point of interest",
      description:"Now let's find out how to edit a point of interest."
    }
  },
];
