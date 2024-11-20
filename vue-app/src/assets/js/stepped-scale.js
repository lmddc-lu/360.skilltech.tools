// This work © 2016 by Sebastian Ott is licensed under Creative Commons Attribution-ShareAlike 3.0

/**
 * Resize an image in multiple steps to avoid aliasing
 * @param {File} file - An image file object
 * @param {number} width - The output width in pixels
 * @param {number} step - The scale factor used for each rescale step
 */
async function steppedScale(file, width=null, height=null, objectFit=null) {
  return new Promise((resolve) => {
    var startWidth = width;
    var startHeight = height;
    const step = 0.5;
    if (!width && !height) {
      resolve(file);
      return;
    };
    let img = new Image();

    img.onload = async function () {


      if (width && height && !objectFit) {
        //We want that all dimensions of the output image fit in a width*height box
        if (img.width/img.height > width/height){
          height = null; // we will compute a new height below keeping the img ratio
        } else {
          width = null;
        }
      }

      else if (width && height && objectFit=='contain') {
        //We want that the image fill the output dimensions. The overflow part will be cropped later.
        if (img.width/img.height > width/height){
          width = null; // we will compute a new height below keeping the img ratio
        } else {
          height = null;
        }
      }

      let canvasWidth, canvasHeight;
      // We will keep the ratio, then we only need the output width or the output height 
      if (width && !height) {
        canvasWidth = width; // destination canvas size
        canvasHeight = canvasWidth * img.height / img.width;
      } else if (height && !width) {
        canvasHeight = height;
        canvasWidth = canvasHeight * img.width / img.height;
      }

      // We dont want to upscale
      if (canvasWidth > img.width || canvasHeight > img.height) {
        // We set the canvas size to the image size, just to draw it one time and convert it to jpeg
        canvasWidth = img.width;
        width = img.width;
        canvasHeight = img.height;
        height = img.height;
      };

      var canvas = document.createElement('canvas');
      var ctx = canvas.getContext("2d");
      var oc = document.createElement('canvas');
      var octx = oc.getContext('2d');
      canvas.height = canvasHeight;
      canvas.width = canvasWidth;

      if (img.width * step > canvas.width) { // For performance avoid unnecessary drawing
        var mul = 1 / step;
        var cur = {
          width: Math.floor(img.width * step),
          height: Math.floor(img.height * step)
        }

        oc.width = cur.width;
        oc.height = cur.height;

        octx.drawImage(img, 0, 0, cur.width, cur.height);

        while (cur.width * step > canvas.width) {
          cur = {
            width: Math.floor(cur.width * step),
            height: Math.floor(cur.height * step)
          };
          octx.drawImage(oc, 0, 0, cur.width * mul, cur.height * mul, 0, 0, cur.width, cur.height);
        }

        ctx.drawImage(oc, 0, 0, cur.width, cur.height, 0, 0, canvas.width, canvas.height);
      } else {
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      }

      // Cropped
      if (objectFit == 'contain'){
        var croppedCanvas = document.createElement('canvas');
        var croppedCtx = croppedCanvas.getContext("2d");
        croppedCanvas.width = startWidth;
        croppedCanvas.height = startHeight;
        croppedCtx.drawImage(
          canvas,
          Math.round((canvas.width - croppedCanvas.width)/2),
          Math.round((canvas.height - croppedCanvas.height)/2),
          croppedCanvas.width,
          croppedCanvas.height,
          0,
          0,
          croppedCanvas.width,
          croppedCanvas.height
        );
        croppedCanvas.toBlob((blob) => {
          blob.name = file.name;
          blob.lastModified = file.lastModified;
          blob.webkitRelativePath = file.webkitRelativePath;
          resolve(blob);
        }, "image/jpeg", 0.8);
      }

      // Not cropped
      canvas.toBlob((blob) => {
        blob.name = file.name;
        blob.lastModified = file.lastModified;
        blob.webkitRelativePath = file.webkitRelativePath;
        resolve(blob);
      }, "image/jpeg", 0.8);
    };
    img.src = URL.createObjectURL(file);
  });
}

export{ steppedScale };
