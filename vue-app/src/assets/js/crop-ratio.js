/**
 * Crop an image file to respect a ratio
 * @param {File} file - An image file object
 * @param {number} ratio - the output width/height ratio
 */
async function cropRatio(file, ratio) {
  return new Promise((resolve) => {
    let img = new Image();

    img.onload = async function () {
      var canvas = document.createElement('canvas');
      var ctx = canvas.getContext("2d");

      if (img.width/img.height > ratio) {
        canvas.height = img.height;
        canvas.width = Math.round(ratio * canvas.height);
      } else if (img.width/img.height < ratio) {
        canvas.width = img.width;
        canvas.height = ratio / canvas.width;
      } else {
        resolve(file)
      }
      console.log(img.width, canvas.width);
      ctx.drawImage(
        img,
        Math.round((img.width - canvas.width)/2),
        Math.round((img.height - canvas.height)/2),
        canvas.width,
        canvas.height,
        0,
        0,
        canvas.width,
        canvas.height
      );

      canvas.toBlob((blob) => {resolve(blob)});
    };
    img.src = URL.createObjectURL(file);
  });
}

export{ cropRatio };
