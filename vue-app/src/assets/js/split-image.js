/**
 * Cut an image file in squares
 * @param {File} file - An image file object
 * @param {number} x - number of columns
 * @param {number} y - number of rows
 */
async function splitImage(file, x=8, y=4) {

  return new Promise((resolve) => {

    if ((x <= 1 && y <=1) || x < 1 || y < 1 ) {
      resolve(file);
      return;
    };

    let img = new Image();

    img.onload = async function () {

      // Force image in 2:1 ratio,
      let input;
      let width = img.width;
      let height = Math.floor(img.width / 2);
      
      if (!Number.isInteger(width / x)) console.warn("Image width is not a multiple of columns");
      if (!Number.isInteger(height / y)) console.warn("Image height is not a multiple of rows");
      
      if (img.height*2 != img.width) {
        // Input canvas
        input = document.createElement('canvas');
        var inputCtx = input.getContext("2d");
        input.width = width;
        input.height = height;

        input.style.background = "red";
        let margin = Math.floor((input.height - img.height) / 2);
        inputCtx.fillStyle = "#222222";
        inputCtx.fillRect(0, 0, width, height);
        inputCtx.drawImage(img, 0, 0, img.width, img.height, 0, margin, img.width, img.height);
      } else {
        input = img;
      }

      let tileWidth = Math.floor(width/x);
      let tileHeight = Math.floor(height/y);

      // Output canvas
      let tile = document.createElement('canvas');
      let tileCtx = tile.getContext('2d');
      tile.width = tileWidth;
      tile.height = tileHeight;

      // The output will be an array of blobs, one for each tile
      var output = Array(x * y);
      var counter = x * y;

      for (let j=0; j<y; j++){
        for (let i=0; i<x; i++){
          tileCtx.drawImage(input, i*tileWidth, j*tileHeight, tileWidth, tileHeight, 0, 0, tileWidth, tileHeight);
          tile.toBlob((blob) => {
            blob.name = file.name;
            blob.lastModified = file.lastModified;
            blob.webkitRelativePath = file.webkitRelativePath;
            let index = i + j * x;
            output[index] = blob;
            counter--;
            if (counter == 0) {
              resolve(output);
            }
          }, "image/jpeg", 0.8);
        }
      }
    };
    img.src = URL.createObjectURL(file);
  });
}

export{ splitImage };
